<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Modifier;

use TestFlowLabs\TestLink\Sync\SyncAction;
use TestFlowLabs\TestLink\Sync\SyncResult;
use TestFlowLabs\TestLink\Adapter\CompositeAdapter;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Sync\Discovery\TestCaseFinder;
use TestFlowLabs\TestLink\Sync\Exception\TestCaseNotFoundException;

/**
 * Orchestrates test file modifications.
 *
 * Uses the adapter pattern to support multiple testing frameworks.
 */
final class TestFileModifier
{
    public function __construct(private readonly OrphanPruner $pruner = new OrphanPruner(), private readonly TestCaseFinder $finder = new TestCaseFinder(), private readonly CompositeAdapter $compositeAdapter = new CompositeAdapter()) {}

    /**
     * Apply sync actions to modify test files.
     *
     * @param  list<SyncAction>  $actions
     * @param  bool  $linkOnly  Use links() instead of linksAndCovers()
     */
    public function apply(array $actions, bool $linkOnly = false): SyncResult
    {
        /** @var array<string, list<string>> $modifiedFiles */
        $modifiedFiles = [];

        /** @var list<string> $errors */
        $errors = [];

        // Group actions by file
        $actionsByFile = $this->groupActionsByFile($actions);

        foreach ($actionsByFile as $filePath => $fileActions) {
            if (!file_exists($filePath)) {
                $errors[] = "File not found: {$filePath}";

                continue;
            }

            $code = file_get_contents($filePath);

            if ($code === false) {
                $errors[] = "Could not read file: {$filePath}";

                continue;
            }

            // Get the appropriate adapter for this file
            $adapter = $this->compositeAdapter->getAdapterForFile($filePath);

            if (!$adapter instanceof \TestFlowLabs\TestLink\Contract\FrameworkAdapterInterface) {
                $errors[] = "No adapter available for file: {$filePath}";

                continue;
            }

            $modifier     = $adapter->getModifier();
            $modified     = false;
            $addedMethods = [];

            foreach ($fileActions as $action) {
                try {
                    $testCase = $this->finder->findTestCase($filePath, $action->testName);

                    // Use the framework-specific modifier
                    $newCode = $modifier->injectLinks(
                        $code,
                        $testCase,
                        $action->methodsToAdd,
                        withCoverage: !$linkOnly,
                    );

                    if ($newCode !== $code) {
                        $code         = $newCode;
                        $modified     = true;
                        $addedMethods = [...$addedMethods, ...$action->methodsToAdd];
                    }
                } catch (TestCaseNotFoundException $e) {
                    $errors[] = $e->getMessage();
                }
            }

            if ($modified) {
                file_put_contents($filePath, $code);
                /** @var list<string> $uniqueMethods */
                $uniqueMethods            = array_values(array_unique($addedMethods));
                $modifiedFiles[$filePath] = $uniqueMethods;
            }
        }

        if ($errors !== []) {
            return SyncResult::withErrors($errors);
        }

        return SyncResult::applied($modifiedFiles);
    }

    /**
     * Prune orphaned link method calls from all test files.
     *
     * Removes links()/linksAndCovers() calls for Pest and
     * #[Links]/#[LinksAndCovers] attributes for PHPUnit.
     *
     * @param  list<string>  $testFiles
     */
    public function prune(array $testFiles, TestLinkRegistry $validRegistry): SyncResult
    {
        /** @var array<string, list<string>> $prunedFiles */
        $prunedFiles = [];

        foreach ($testFiles as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }

            $orphans = $this->pruner->findOrphans($filePath, $validRegistry);

            if ($orphans === []) {
                continue;
            }

            $code = file_get_contents($filePath);

            if ($code === false) {
                continue;
            }

            $newCode = $this->pruner->prune($code, $orphans);

            if ($newCode !== $code) {
                file_put_contents($filePath, $newCode);
                $prunedFiles[$filePath] = array_map(fn (OrphanedCall $o): string => $o->method, $orphans);
            }
        }

        return SyncResult::applied([], $prunedFiles);
    }

    /**
     * Group actions by file path.
     *
     * @param  list<SyncAction>  $actions
     *
     * @return array<string, list<SyncAction>>
     */
    private function groupActionsByFile(array $actions): array
    {
        $grouped = [];

        foreach ($actions as $action) {
            $grouped[$action->testFile][] = $action;
        }

        return $grouped;
    }
}
