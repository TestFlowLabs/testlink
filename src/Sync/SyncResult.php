<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync;

/**
 * Value object for sync command results.
 */
final readonly class SyncResult
{
    /**
     * @param  array<string, list<string>>  $modifiedFiles  file path => list of added methods
     * @param  array<string, list<string>>  $prunedFiles  file path => list of pruned methods
     * @param  list<string>  $errors
     * @param  list<SyncAction>  $actions  planned actions (for dry-run)
     * @param  array<string, list<string>>  $seeActions  methodIdentifier => list of test references (for @see tags)
     * @param  array<string, list<string>>  $seePruneActions  methodIdentifier => list of test references to remove
     * @param  list<ReverseTestedByAction>  $reverseActions  planned reverse actions (test → production)
     */
    public function __construct(
        public bool $isDryRun = false,
        public int $linksAdded = 0,
        public int $linksPruned = 0,
        public int $seeTagsAdded = 0,
        public int $seeTagsPruned = 0,
        public int $testedByAdded = 0,
        public int $filesModified = 0,
        public array $modifiedFiles = [],
        public array $prunedFiles = [],
        public array $errors = [],
        public array $actions = [],
        public array $seeActions = [],
        public array $seePruneActions = [],
        public array $reverseActions = [],
    ) {}

    /**
     * Create a dry-run result with planned actions.
     *
     * @param  list<SyncAction>  $actions
     * @param  array<string, list<string>>  $seeActions  methodIdentifier => list of test references
     * @param  array<string, list<string>>  $seePruneActions  methodIdentifier => list of test references to remove
     * @param  list<ReverseTestedByAction>  $reverseActions  reverse sync actions (test → production)
     */
    public static function dryRun(array $actions, array $seeActions = [], array $seePruneActions = [], array $reverseActions = []): self
    {
        // Build modifiedFiles from actions for CLI display
        $modifiedFiles = [];

        foreach ($actions as $action) {
            if (!isset($modifiedFiles[$action->testFile])) {
                $modifiedFiles[$action->testFile] = [];
            }

            foreach ($action->methodsToAdd as $method) {
                $modifiedFiles[$action->testFile][] = $method;
            }
        }

        // Count @see tags to add
        $seeTagsCount = 0;
        foreach ($seeActions as $references) {
            $seeTagsCount += count($references);
        }

        // Count @see tags to prune
        $seePruneCount = 0;
        foreach ($seePruneActions as $references) {
            $seePruneCount += count($references);
        }

        return new self(
            isDryRun: true,
            linksAdded: array_sum(array_map(fn (SyncAction $a): int => count($a->methodsToAdd), $actions)),
            seeTagsAdded: $seeTagsCount,
            seeTagsPruned: $seePruneCount,
            testedByAdded: count($reverseActions),
            modifiedFiles: $modifiedFiles,
            actions: $actions,
            seeActions: $seeActions,
            seePruneActions: $seePruneActions,
            reverseActions: $reverseActions,
        );
    }

    /**
     * Create a result for applied changes.
     *
     * @param  array<string, list<string>>  $modifiedFiles
     * @param  array<string, list<string>>  $prunedFiles
     */
    public static function applied(
        array $modifiedFiles,
        array $prunedFiles = [],
    ): self {
        $linksAdded  = array_sum(array_map(count(...), $modifiedFiles));
        $linksPruned = array_sum(array_map(count(...), $prunedFiles));

        return new self(
            isDryRun: false,
            linksAdded: $linksAdded,
            linksPruned: $linksPruned,
            filesModified: count(array_unique([...array_keys($modifiedFiles), ...array_keys($prunedFiles)])),
            modifiedFiles: $modifiedFiles,
            prunedFiles: $prunedFiles,
        );
    }

    /**
     * Create a result with errors.
     *
     * @param  list<string>  $errors
     */
    public static function withErrors(array $errors): self
    {
        return new self(errors: $errors);
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Check if any changes were made (or would be made in dry-run).
     */
    public function hasChanges(): bool
    {
        return $this->linksAdded > 0 || $this->linksPruned > 0 || $this->seeTagsAdded > 0 || $this->seeTagsPruned > 0 || $this->testedByAdded > 0;
    }

    /**
     * Merge another result into this one.
     */
    public function merge(self $other): self
    {
        return new self(
            isDryRun: $this->isDryRun,
            linksAdded: $this->linksAdded + $other->linksAdded,
            linksPruned: $this->linksPruned + $other->linksPruned,
            seeTagsAdded: $this->seeTagsAdded + $other->seeTagsAdded,
            seeTagsPruned: $this->seeTagsPruned + $other->seeTagsPruned,
            testedByAdded: $this->testedByAdded + $other->testedByAdded,
            filesModified: count(array_unique([
                ...array_keys($this->modifiedFiles),
                ...array_keys($this->prunedFiles),
                ...array_keys($other->modifiedFiles),
                ...array_keys($other->prunedFiles),
            ])),
            modifiedFiles: $this->mergeFileMaps($this->modifiedFiles, $other->modifiedFiles),
            prunedFiles: $this->mergeFileMaps($this->prunedFiles, $other->prunedFiles),
            errors: [...$this->errors, ...$other->errors],
            actions: [...$this->actions, ...$other->actions],
            seeActions: $this->mergeFileMaps($this->seeActions, $other->seeActions),
            seePruneActions: $this->mergeFileMaps($this->seePruneActions, $other->seePruneActions),
            reverseActions: [...$this->reverseActions, ...$other->reverseActions],
        );
    }

    /**
     * Merge two file maps together.
     *
     * @param  array<string, list<string>>  $first
     * @param  array<string, list<string>>  $second
     *
     * @return array<string, list<string>>
     */
    private function mergeFileMaps(array $first, array $second): array
    {
        $result = $first;

        foreach ($second as $file => $methods) {
            $result[$file] = isset($result[$file]) ? [...$result[$file], ...$methods] : $methods;
        }

        return $result;
    }
}
