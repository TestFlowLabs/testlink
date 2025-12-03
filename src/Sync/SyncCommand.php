<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync;

use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Sync\Discovery\TestCaseFinder;
use TestFlowLabs\TestLink\Sync\Modifier\TestFileModifier;
use TestFlowLabs\TestLink\Sync\Discovery\TestFileDiscovery;
use TestFlowLabs\TestLink\Sync\Exception\TestCaseNotFoundException;
use TestFlowLabs\TestLink\Sync\Exception\TestFileNotFoundException;

/**
 * Main orchestrator for the sync command.
 *
 * Scans test files for #[LinksAndCovers]/#[Links] attributes and
 * synchronizes coverage links across test files.
 *
 * Supports both Pest and PHPUnit test files through the adapter pattern.
 */
final class SyncCommand
{
    public function __construct(private readonly AttributeScanner $scanner = new AttributeScanner(), private readonly TestFileDiscovery $discovery = new TestFileDiscovery(), private readonly TestCaseFinder $finder = new TestCaseFinder(), private readonly TestFileModifier $modifier = new TestFileModifier(), private readonly SyncReporter $reporter = new SyncReporter()) {}

    /**
     * Execute the sync command.
     */
    public function execute(SyncOptions $options): SyncResult
    {
        // 1. Scan test files for #[LinksAndCovers]/#[Links] attributes
        $registry = new TestLinkRegistry();

        if ($options->path !== null) {
            $this->scanner->setProjectRoot($options->path);
        }

        $this->scanner->discoverAndScan($registry);

        // 2. Build sync actions from the registry
        $actions = $this->buildSyncActions($registry);

        // 3. Handle dry-run mode
        if ($options->dryRun) {
            $this->reporter->reportDryRun($actions);

            return SyncResult::dryRun($actions);
        }

        // 4. If no actions, report and exit
        if ($actions === []) {
            $this->reporter->reportNoChanges();

            return new SyncResult();
        }

        // 5. Apply modifications
        $result = $this->modifier->apply($actions, $options->linkOnly);

        // 6. Handle pruning if requested
        if ($options->prune && $options->force) {
            $testFiles   = $this->collectTestFiles($actions);
            $pruneResult = $this->modifier->prune($testFiles, $registry);
            $result      = $result->merge($pruneResult);
        }

        // 7. Report results
        $this->reporter->reportResults($result);

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
}
