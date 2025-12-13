<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync;

use TestFlowLabs\TestLink\DocBlock\SeeTagRegistry;
use TestFlowLabs\TestLink\Scanner\PestLinkScanner;
use TestFlowLabs\TestLink\DocBlock\DocBlockScanner;
use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Sync\Discovery\TestCaseFinder;
use TestFlowLabs\TestLink\Sync\Modifier\TestFileModifier;
use TestFlowLabs\TestLink\Sync\Discovery\TestFileDiscovery;
use TestFlowLabs\TestLink\Sync\Modifier\ProductionFileModifier;
use TestFlowLabs\TestLink\Sync\Discovery\ProductionFileDiscovery;
use TestFlowLabs\TestLink\Sync\Exception\TestCaseNotFoundException;
use TestFlowLabs\TestLink\Sync\Exception\TestFileNotFoundException;
use TestFlowLabs\TestLink\Sync\Modifier\ProductionAttributeModifier;
use TestFlowLabs\TestLink\Sync\Exception\ProductionFileNotFoundException;

/**
 * Main orchestrator for the sync command.
 *
 * Scans test files for #[LinksAndCovers]/#[Links] attributes and
 * synchronizes coverage links across test files.
 *
 * Also handles @see tags in docblocks for bidirectional linking.
 *
 * Supports both Pest and PHPUnit test files through the adapter pattern.
 */
final class SyncCommand
{
    public function __construct(
        private readonly AttributeScanner $scanner = new AttributeScanner(),
        private readonly PestLinkScanner $pestScanner = new PestLinkScanner(),
        private readonly TestFileDiscovery $discovery = new TestFileDiscovery(),
        private readonly TestCaseFinder $finder = new TestCaseFinder(),
        private readonly TestFileModifier $modifier = new TestFileModifier(),
        private readonly ProductionFileModifier $productionModifier = new ProductionFileModifier(),
        private readonly DocBlockScanner $docBlockScanner = new DocBlockScanner(),
        private readonly ProductionFileDiscovery $productionDiscovery = new ProductionFileDiscovery(),
        private readonly ProductionAttributeModifier $productionAttributeModifier = new ProductionAttributeModifier(),
    ) {}

    /**
     * Execute the sync command.
     */
    public function execute(SyncOptions $options): SyncResult
    {
        // 1. Scan BOTH test and production files for attributes
        $registry = new TestLinkRegistry();

        if ($options->path !== null) {
            $this->scanner->setProjectRoot($options->path);
            $this->pestScanner->setProjectRoot($options->path);
            $this->docBlockScanner->setProjectRoot($options->path);
            $this->productionDiscovery->setProjectRoot($options->path);
        }

        // Scan test files for #[LinksAndCovers]/#[Links] and production files for #[TestedBy]
        $this->scanner->discoverAndScanAll($registry);

        // Also scan Pest test files for ->linksAndCovers() method chains
        $this->pestScanner->scan($registry);

        // 2. Scan existing @see tags
        $seeRegistry = new SeeTagRegistry();
        $this->docBlockScanner->scan($seeRegistry);

        // 3. Build sync actions from the registry
        $actions = $this->buildSyncActions($registry);

        // 4. Build @see actions for production methods
        $seeActions = $this->buildSeeActions($registry, $seeRegistry);

        // 5. Build reverse #[TestedBy] actions for production methods (test → production)
        $reverseActions = $this->buildReverseTestedByActions($registry);

        // 6. Build prune actions if requested (computed before dry-run so dry-run can show them)
        $seePruneActions = [];

        if ($options->prune && $options->force) {
            $seePruneActions = $this->buildSeePruneActions($seeRegistry, $registry);
        }

        // 7. Handle dry-run mode - show all actions without applying
        if ($options->dryRun) {
            // Let the CLI wrapper handle the output
            return SyncResult::dryRun($actions, $seeActions, $seePruneActions, $reverseActions);
        }

        // 8. If no actions and no @see actions and no reverse actions, exit
        if ($actions === [] && $seeActions === [] && $seePruneActions === [] && $reverseActions === []) {
            // Let the CLI wrapper handle the output
            return new SyncResult();
        }

        // 9. Apply test file modifications
        $result = $actions !== []
            ? $this->modifier->apply($actions, $options->linkOnly)
            : new SyncResult();

        // 10. Apply @see tag modifications to production files
        if ($seeActions !== []) {
            $seeResult = $this->productionModifier->addSeeTags($seeActions);
            $result    = $result->merge($seeResult);
        }

        // 11. Apply reverse #[TestedBy] attributes to production files (test → production)
        if ($reverseActions !== []) {
            $reverseResult = $this->productionAttributeModifier->apply($reverseActions);
            $result        = $result->merge($reverseResult);
        }

        // 12. Handle pruning if requested
        if ($options->prune && $options->force) {
            // Collect all test files that might have orphan links (not just from actions)
            $testFiles   = $this->collectAllTestFiles($options->path);
            $pruneResult = $this->modifier->prune($testFiles, $registry);
            $result      = $result->merge($pruneResult);

            // Apply @see prune actions
            if ($seePruneActions !== []) {
                $seePruneResult = $this->productionModifier->removeSeeTags($seePruneActions);
                $result         = $result->merge($seePruneResult);
            }
        }

        // 11. Let the CLI wrapper handle the result reporting
        return $result;
    }

    /**
     * Build sync actions from the registry.
     *
     * @return list<SyncAction>
     */
    private function buildSyncActions(TestLinkRegistry $registry): array
    {
        $actions = [];

        // Get all methods from #[TestedBy] attributes that need linksAndCovers in test files
        foreach ($registry->getTestedByLinks() as $methodIdentifier => $testIdentifiers) {
            foreach ($testIdentifiers as $testIdentifier) {
                try {
                    $testFile = $this->discovery->findTestFile($testIdentifier);
                    $testName = $this->discovery->extractTestName($testIdentifier);

                    // Check if the test already has this link
                    $testCase = null;

                    try {
                        $testCase = $this->finder->findTestCase($testFile, $testName);

                        // Skip if already has this link (linksAndCovers/links for Pest, #[LinksAndCovers]/#[Links] for PHPUnit)
                        if ($testCase->hasCoversMethod($methodIdentifier)) {
                            continue;
                        }
                    } catch (TestCaseNotFoundException) {
                        // Test case not found, still create action for error reporting
                    }

                    $actions[] = new SyncAction(
                        testFile: $testFile,
                        testIdentifier: $testIdentifier,
                        testName: $testName,
                        methodIdentifier: $methodIdentifier,
                        methodsToAdd: [$methodIdentifier],
                        testCase: $testCase,
                    );
                } catch (TestFileNotFoundException) {
                    // Skip tests we can't find
                    continue;
                }
            }
        }

        return $actions;
    }

    /**
     * Build reverse sync actions from test-side links (test → production).
     *
     * Creates actions to add #[TestedBy] attributes to production methods
     * based on existing linksAndCovers()/LinksAndCovers in test files.
     *
     * @return list<ReverseTestedByAction>
     */
    private function buildReverseTestedByActions(TestLinkRegistry $registry): array
    {
        $actions = [];

        // Get all test-side links (from linksAndCovers/LinksAndCovers)
        foreach ($registry->getAllLinksByTest() as $testIdentifier => $methodIdentifiers) {
            foreach ($methodIdentifiers as $methodIdentifier) {
                // Check if production already has #[TestedBy] pointing to this test
                $existingTests = $registry->getTestedByForMethod($methodIdentifier);

                if (in_array($testIdentifier, $existingTests, true)) {
                    continue; // Already has this #[TestedBy]
                }

                // Try to find the production file
                try {
                    $productionFile = $this->productionDiscovery->findProductionFile($methodIdentifier);
                    $className      = $this->productionDiscovery->extractClassName($methodIdentifier);
                    $methodName     = $this->productionDiscovery->extractMethodName($methodIdentifier);

                    if ($methodName === null) {
                        continue; // Class-level links not supported for reverse sync
                    }

                    $actions[] = new ReverseTestedByAction(
                        productionFile: $productionFile,
                        methodIdentifier: $methodIdentifier,
                        testIdentifier: $testIdentifier,
                        className: $className,
                        methodName: $methodName,
                    );
                } catch (ProductionFileNotFoundException) {
                    // Skip methods we can't find
                    continue;
                }
            }
        }

        return $actions;
    }

    /**
     * Collect all test files that might contain links.
     *
     * Used for pruning to scan all test files, not just those from sync actions.
     *
     * @return list<string>
     */
    private function collectAllTestFiles(?string $projectRoot): array
    {
        $scanner = new PestLinkScanner();

        if ($projectRoot !== null) {
            $scanner->setProjectRoot($projectRoot);
        }

        return $scanner->discoverPestFiles();
    }

    /**
     * Build @see actions for production methods.
     *
     * Creates a map of methodIdentifier => list of test references
     * for methods that need @see tags added.
     *
     * @return array<string, list<string>>
     */
    private function buildSeeActions(TestLinkRegistry $registry, SeeTagRegistry $seeRegistry): array
    {
        $seeActions = [];

        // Get all #[TestedBy] links from production code
        foreach ($registry->getTestedByLinks() as $methodIdentifier => $testIdentifiers) {
            foreach ($testIdentifiers as $testIdentifier) {
                // Format as @see reference (e.g., \Tests\UserServiceTest::testCreate)
                $reference = '\\'.ltrim($testIdentifier, '\\');

                // Check if @see already exists for this method pointing to this test
                if ($seeRegistry->hasProductionSee($methodIdentifier, $reference)) {
                    continue;
                }

                if (!isset($seeActions[$methodIdentifier])) {
                    $seeActions[$methodIdentifier] = [];
                }

                $seeActions[$methodIdentifier][] = $reference;
            }
        }

        return $seeActions;
    }

    /**
     * Build @see prune actions for orphan @see tags.
     *
     * Finds @see tags in production code that point to tests that
     * no longer exist or are no longer linked.
     *
     * @return array<string, list<string>>
     */
    private function buildSeePruneActions(SeeTagRegistry $seeRegistry, TestLinkRegistry $registry): array
    {
        $pruneActions = [];

        // Get all valid production method identifiers from the registry
        $validProductionMethods = $registry->getAllMethods();

        // Also include methods from #[TestedBy]
        foreach (array_keys($registry->getTestedByLinks()) as $methodIdentifier) {
            $validProductionMethods[] = $methodIdentifier;
        }

        $validProductionMethods = array_unique($validProductionMethods);

        // Get all valid test identifiers from the registry
        $validTests = array_keys($registry->getAllLinksByTest());

        // Also include tests from #[TestedBy]
        foreach ($registry->getTestedByLinks() as $testIdentifiers) {
            foreach ($testIdentifiers as $testIdentifier) {
                $validTests[] = $testIdentifier;
            }
        }

        $validTests = array_unique($validTests);

        // Find orphan @see tags
        $orphans = $seeRegistry->findOrphans($validProductionMethods, $validTests);

        foreach ($orphans as $entry) {
            // Only process production @see entries
            if (!$entry->isProduction()) {
                continue;
            }

            $methodIdentifier = $entry->getMethodIdentifier();

            if ($methodIdentifier === null) {
                continue;
            }

            if (!isset($pruneActions[$methodIdentifier])) {
                $pruneActions[$methodIdentifier] = [];
            }

            $pruneActions[$methodIdentifier][] = $entry->reference;
        }

        return $pruneActions;
    }
}
