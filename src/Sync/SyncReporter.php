<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync;

/**
 * Reports sync command results to the console.
 */
final class SyncReporter
{
    private const GREEN = "\033[32m";

    private const YELLOW = "\033[33m";

    private const RED = "\033[31m";

    private const CYAN = "\033[36m";

    private const RESET = "\033[0m";

    private const BOLD = "\033[1m";

    private const DIM = "\033[2m";

    /** @var resource */
    private $output;

    /**
     * @param  resource|null  $output
     */
    public function __construct($output = null)
    {
        $this->output = $output ?? STDOUT;
    }

    /**
     * Report dry-run results (planned changes).
     *
     * @param  list<SyncAction>  $actions
     */
    public function reportDryRun(array $actions): void
    {
        $this->writeLine('');
        $this->writeLine(self::BOLD.'Scanning test files for coverage links...'.self::RESET);
        $this->writeLine('');

        if ($actions === []) {
            $this->writeLine(self::YELLOW.'No links found to sync.'.self::RESET);

            return;
        }

        $this->writeLine(sprintf(
            'Found %s%d%s link(s) to sync:',
            self::BOLD,
            count($actions),
            self::RESET
        ));
        $this->writeLine('');

        foreach ($actions as $action) {
            $this->writeLine('  '.self::CYAN.$action->methodIdentifier.self::RESET);
            $this->writeLine('  └── Test: "'.$action->testName.'"');
            $this->writeLine('      → File: '.self::DIM.$action->testFile.self::RESET);
            $this->writeLine('      → Will add: '.self::GREEN.$action->getPestLinkDisplay().self::RESET);
            $this->writeLine('');
        }

        $this->writeLine(self::YELLOW.'Run without --dry-run to apply changes.'.self::RESET);
    }

    /**
     * Report applied changes.
     */
    public function reportResults(SyncResult $result): void
    {
        $this->writeLine('');

        if ($result->hasErrors()) {
            $this->writeLine(self::RED.self::BOLD.'Sync failed with errors:'.self::RESET);
            $this->writeLine('');

            foreach ($result->errors as $error) {
                $this->writeLine('  '.self::RED.'✗'.self::RESET.' '.$error);
            }

            $this->writeLine('');

            return;
        }

        if (!$result->hasChanges()) {
            $this->writeLine(self::GREEN.'✓ All coverage links are already in sync.'.self::RESET);

            return;
        }

        $this->writeLine(self::GREEN.self::BOLD.'✓ Sync complete!'.self::RESET);
        $this->writeLine('');

        if ($result->linksAdded > 0) {
            $this->writeLine(sprintf(
                '  %s%d%s linksAndCovers()/links() call(s) added',
                self::GREEN,
                $result->linksAdded,
                self::RESET
            ));
        }

        if ($result->linksPruned > 0) {
            $this->writeLine(sprintf(
                '  %s%d%s orphaned call(s) removed',
                self::YELLOW,
                $result->linksPruned,
                self::RESET
            ));
        }

        $this->writeLine('');
        $this->writeLine('Modified files:');

        foreach ($result->modifiedFiles as $file => $methods) {
            $methodCount = count($methods);
            $this->writeLine(sprintf(
                '  %s- %s%s (%d link(s) added)',
                self::GREEN,
                self::RESET,
                $file,
                $methodCount
            ));
        }

        foreach ($result->prunedFiles as $file => $methods) {
            $methodCount = count($methods);
            $this->writeLine(sprintf(
                '  %s- %s%s (%d link(s) pruned)',
                self::YELLOW,
                self::RESET,
                $file,
                $methodCount
            ));
        }

        $this->writeLine('');
    }

    /**
     * Report when no changes are needed.
     */
    public function reportNoChanges(): void
    {
        $this->writeLine('');
        $this->writeLine(self::GREEN.'✓ All coverage links are already in sync.'.self::RESET);
        $this->writeLine('');
    }

    /**
     * Report validation error for --prune without --force.
     */
    public function reportPruneRequiresForce(): void
    {
        $this->writeLine('');
        $this->writeLine(self::RED.self::BOLD.'Error:'.self::RESET.' The --prune option requires --force to confirm deletion.');
        $this->writeLine('');
        $this->writeLine('This is a safety measure to prevent accidental removal of linksAndCovers()/links() calls.');
        $this->writeLine('');
        $this->writeLine('Usage: '.self::CYAN.'testlink sync --prune --force'.self::RESET);
        $this->writeLine('');
    }

    /**
     * Write a line to output.
     */
    private function writeLine(string $message): void
    {
        fwrite($this->output, $message."\n");
    }
}
