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
     */
    public function __construct(
        public bool $isDryRun = false,
        public int $linksAdded = 0,
        public int $linksPruned = 0,
        public int $filesModified = 0,
        public array $modifiedFiles = [],
        public array $prunedFiles = [],
        public array $errors = [],
        public array $actions = [],
    ) {}

    /**
     * Create a dry-run result with planned actions.
     *
     * @param  list<SyncAction>  $actions
     */
    public static function dryRun(array $actions): self
    {
        return new self(
            isDryRun: true,
            linksAdded: array_sum(array_map(fn (SyncAction $a): int => count($a->methodsToAdd), $actions)),
            actions: $actions,
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
        return $this->linksAdded > 0 || $this->linksPruned > 0;
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
