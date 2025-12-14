<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console\Command;

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Sync\SyncResult;
use TestFlowLabs\TestLink\Sync\SyncOptions;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\Sync\SyncCommand as SyncCommandCore;

/**
 * Sync command - synchronizes coverage links across test files.
 */
final class SyncCommand
{
    /**
     * Execute the sync command.
     */
    public function execute(ArgumentParser $parser, Output $output): int
    {
        $dryRun   = $parser->hasOption('dry-run');
        $linkOnly = $parser->hasOption('link-only');
        $prune    = $parser->hasOption('prune');
        $force    = $parser->hasOption('force');
        $path     = $parser->getString('path');

        // Validate prune requires force
        if ($prune && !$force) {
            $output->error('The --prune option requires --force to confirm deletion.');
            $output->newLine();
            $output->writeln('    testlink sync --prune --force');
            $output->newLine();

            return 1;
        }

        $options = new SyncOptions(
            dryRun: $dryRun,
            linkOnly: $linkOnly,
            prune: $prune,
            force: $force,
            path: $path,
        );

        $output->title('Syncing Coverage Links');

        if ($dryRun) {
            $output->info('Running in dry-run mode. No files will be modified.');
            $output->newLine();
        }

        $command = new SyncCommandCore();
        $result  = $command->execute($options);

        return $this->reportResult($result, $output, $dryRun);
    }

    /**
     * Report sync result.
     */
    private function reportResult(SyncResult $result, Output $output, bool $dryRun): int
    {
        // Handle errors
        if ($result->hasErrors()) {
            $output->section('Errors');

            foreach ($result->errors as $error) {
                $output->error($error);
            }

            $output->newLine();

            return 1;
        }

        // Get modified files and @see actions
        $modifiedFiles   = $result->modifiedFiles;
        $prunedFiles     = $result->prunedFiles;
        $seeActions      = $result->seeActions;
        $seePruneActions = $result->seePruneActions;
        $reverseActions  = $result->reverseActions;

        if ($modifiedFiles === [] && $prunedFiles === [] && $seeActions === [] && $seePruneActions === [] && $reverseActions === []) {
            $output->success('No changes needed. All links are up to date.');
            $output->newLine();

            return 0;
        }

        // Report modifications
        if ($modifiedFiles !== []) {
            $action = $dryRun ? 'Would modify' : 'Modified';
            $output->section("{$action} Files");

            foreach ($modifiedFiles as $file => $methods) {
                $output->writeln('    '.$output->green('✓').' '.$this->shortenPath($file));

                foreach ($methods as $method) {
                    $output->writeln('      + '.$output->cyan($this->shortenMethod($method)));
                }
            }

            $output->newLine();
        }

        // Report pruned files
        if ($prunedFiles !== []) {
            $action = $dryRun ? 'Would prune from' : 'Pruned from';
            $output->section("{$action} Files");

            foreach ($prunedFiles as $file => $methods) {
                $output->writeln('    '.$output->yellow('−').' '.$this->shortenPath($file));

                foreach ($methods as $method) {
                    $output->writeln('      - '.$output->gray($this->shortenMethod($method)));
                }
            }

            $output->newLine();
        }

        // Report @see tag additions
        if ($seeActions !== []) {
            $action = $dryRun ? 'Would add @see tags to' : 'Added @see tags to';
            $output->section($action);

            foreach ($seeActions as $methodIdentifier => $references) {
                $output->writeln('    '.$output->green('✓').' '.$this->shortenMethod($methodIdentifier));

                foreach ($references as $reference) {
                    $output->writeln('      + '.$output->cyan('@see '.$this->shortenMethod($reference)));
                }
            }

            $output->newLine();
        }

        // Report @see tag removals
        if ($seePruneActions !== []) {
            $action = $dryRun ? 'Would remove @see tags from' : 'Removed @see tags from';
            $output->section($action);

            foreach ($seePruneActions as $methodIdentifier => $references) {
                $output->writeln('    '.$output->yellow('−').' '.$this->shortenMethod($methodIdentifier));

                foreach ($references as $reference) {
                    $output->writeln('      - '.$output->gray('@see '.$this->shortenMethod($reference)));
                }
            }

            $output->newLine();
        }

        // Report reverse #[TestedBy] additions (test → production)
        if ($reverseActions !== []) {
            $action = $dryRun ? 'Would add #[TestedBy] to' : 'Added #[TestedBy] to';
            $output->section($action);

            foreach ($reverseActions as $reverseAction) {
                $output->writeln('    '.$output->green('✓').' '.$this->shortenMethod($reverseAction->methodIdentifier));
                $output->writeln('      + '.$output->cyan('#[TestedBy] '.$this->shortenMethod($reverseAction->testIdentifier)));
            }

            $output->newLine();
        }

        // Summary
        $modifiedCount      = count($modifiedFiles);
        $prunedCount        = count($prunedFiles);
        $seeTagCount        = $result->seeTagsAdded;
        $seePruneCount      = $result->seeTagsPruned;
        $reverseActionCount = count($reverseActions);

        $output->summaryHeader($dryRun);
        $output->summaryLine('Files modified', $modifiedCount);
        $output->summaryLine('Files pruned', $prunedCount);
        $output->summaryLine('@see tags added', $seeTagCount);
        $output->summaryLine('@see tags removed', $seePruneCount);
        $output->summaryLine('#[TestedBy] added', $reverseActionCount);

        if ($dryRun) {
            $output->summaryComplete('Dry-run complete. Use --no-dry-run to apply.');
        } else {
            $output->summaryComplete('Sync complete.');
        }

        $output->newLine();

        return 0;
    }

    /**
     * Shorten file path for display.
     */
    private function shortenPath(string $path): string
    {
        $cwd = getcwd();

        if ($cwd !== false && str_starts_with($path, $cwd)) {
            return substr($path, strlen($cwd) + 1);
        }

        return $path;
    }

    /**
     * Shorten method identifier for display.
     */
    private function shortenMethod(string $method): string
    {
        // Convert App\Services\UserService::create to UserService::create
        if (str_contains($method, '\\')) {
            $parts = explode('\\', $method);

            return array_pop($parts);
        }

        return $method;
    }
}
