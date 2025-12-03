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

        // Get modified files
        $modifiedFiles = $result->modifiedFiles;
        $prunedFiles   = $result->prunedFiles;

        if ($modifiedFiles === [] && $prunedFiles === []) {
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

        // Summary
        $modifiedCount = count($modifiedFiles);
        $prunedCount   = count($prunedFiles);

        if ($dryRun) {
            $output->info("Dry run complete. Would modify {$modifiedCount} file(s).");

            if ($prunedCount > 0) {
                $output->info("Would prune from {$prunedCount} file(s).");
            }

            $output->newLine();
            $output->writeln('    Run without --dry-run to apply changes:');
            $output->writeln('    testlink sync');
        } else {
            $output->success("Sync complete. Modified {$modifiedCount} file(s).");

            if ($prunedCount > 0) {
                $output->success("Pruned from {$prunedCount} file(s).");
            }
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
