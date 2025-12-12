<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync;

use TestFlowLabs\TestLink\DocBlock\SeeTagRegistry;
use TestFlowLabs\TestLink\DocBlock\DocBlockScanner;
use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Sync\Discovery\TestCaseFinder;
use TestFlowLabs\TestLink\Sync\Modifier\TestFileModifier;
use TestFlowLabs\TestLink\Sync\Discovery\TestFileDiscovery;
use TestFlowLabs\TestLink\Sync\Modifier\ProductionFileModifier;
use TestFlowLabs\TestLink\Sync\Exception\TestCaseNotFoundException;
use TestFlowLabs\TestLink\Sync\Exception\TestFileNotFoundException;

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
        private readonly TestFileDiscovery $discovery = new TestFileDiscovery(),
        private readonly TestCaseFinder $finder = new TestCaseFinder(),
        private readonly TestFileModifier $modifier = new TestFileModifier(),
        private readonly ProductionFileModifier $productionModifier = new ProductionFileModifier(),
        private readonly DocBlockScanner $docBlockScanner = new DocBlockScanner(),
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
            $this->docBlockScanner->setProjectRoot($options->path);
        }

        // Scan test files for #[LinksAndCovers]/#[Links] and production files for #[TestedBy]
        $this->scanner->discoverAndScanAll($registry);

        // 2. Scan existing @see tags
        $seeRegistry = new SeeTagRegistry();
        $this->docBlockScanner->scan($seeRegistry);

        // 3. Build sync actions from the registry
        $actions = $this->buildSyncActions($registry);

        // 4. Build @see actions for production methods
        $seeActions = $this->buildSeeActions($registry, $seeRegistry);

        // 5. Handle dry-run mode
        if ($options->dryRun) {
            // Let the CLI wrapper handle the output
            return SyncResult::dryRun($actions);
        }

        // 6. If no actions and no @see actions, exit
        if ($actions === [] && $seeActions === []) {
            // Let the CLI wrapper handle the output
            return new SyncResult();
        }

        // 7. Apply test file modifications
        $result = $actions !== []
            ? $this->modifier->apply($actions, $options->linkOnly)
            : new SyncResult();

        // 8. Apply @see tag modifications to production files
        if ($seeActions !== []) {
            $seeResult = $this->productionModifier->addSeeTags($seeActions);
            $result    = $result->merge($seeResult);
        }

        // 9. Handle pruning if requested
        if ($options->prune && $options->force) {
            $testFiles   = $this->collectTestFiles($actions);
            $pruneResult = $this->modifier->prune($testFiles, $registry);
            $result      = $result->merge($pruneResult);

            // Also prune orphan @see tags
            $seePruneActions = $this->buildSeePruneActions($seeRegistry, $registry);

            if ($seePruneActions !== []) {
                $seePruneResult = $this->productionModifier->removeSeeTags($seePruneActions);
                $result         = $result->merge($seePruneResult);
            }
        }

        // 10. Let the CLI wrapper handle the result reporting
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

        // Get all methods that have coverage links in test files
        foreach ($registry->getAllLinks() as $methodIdentifier => $testIdentifiers) {
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
     * Collect unique test files from actions.
     *
     * @param  list<SyncAction>  $actions
     *
     * @return list<string>
     */
    private function collectTestFiles(array $actions): array
    {
        $files = [];

        foreach ($actions as $action) {
            $files[$action->testFile] = true;
        }

        return array_keys($files);
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

        // Get all valid test identifiers from the registry
        $validTests = array_keys($registry->getAllLinksByTest());

        // Also include tests from #[TestedBy]
        foreach ($registry->getTestedByLinks() as $testIdentifiers) {
            foreach ($testIdentifiers as $testIdentifier) {
                $validTests[] = $testIdentifier;
            }
        }

        $validTests = array_unique($validTests);

        // Find orphan @see tags in production code
        $orphans = $seeRegistry->findOrphans([], $validTests);

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
