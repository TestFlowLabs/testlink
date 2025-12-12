<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console\Command;

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\Placeholder\PlaceholderScanner;
use TestFlowLabs\TestLink\Placeholder\PlaceholderModifier;
use TestFlowLabs\TestLink\Placeholder\PlaceholderRegistry;
use TestFlowLabs\TestLink\Placeholder\PlaceholderResolver;

/**
 * Pair command - resolves placeholder markers into real test-production links.
 *
 * Replaces placeholders like @A, @user-create in:
 * - Production: #[TestedBy('@A')] → #[TestedBy('Tests\Unit\UserTest', 'testCreate')]
 * - Tests: linksAndCovers('@A') → linksAndCovers(UserService::class.'::create')
 */
final class PairCommand
{
    /**
     * Execute the pair command.
     */
    public function execute(ArgumentParser $parser, Output $output): int
    {
        $dryRun      = $parser->hasOption('dry-run');
        $placeholder = $parser->getString('placeholder');
        $path        = $parser->getString('path');

        $output->title('Pairing Placeholders');

        if ($dryRun) {
            $output->info('Running in dry-run mode. No files will be modified.');
            $output->newLine();
        }

        // Scan for placeholders
        $registry = new PlaceholderRegistry();
        $scanner  = new PlaceholderScanner();

        // Set custom project root if path provided
        if ($path !== null) {
            $scanner->setProjectRoot($path);
        }

        $output->writeln('  Scanning for placeholders...');
        $scanner->scan($registry);

        $placeholderIds = $registry->getAllPlaceholderIds();

        if ($placeholderIds === []) {
            $output->newLine();
            $output->warning('No placeholders found.');
            $output->newLine();
            $output->writeln('  Placeholders use @syntax, for example:');
            $output->writeln('    Production: #[TestedBy(\'@A\')]');
            $output->writeln('    Test (Pest): ->linksAndCovers(\'@A\')');
            $output->writeln('    Test (PHPUnit): #[LinksAndCovers(\'@A\')]');
            $output->newLine();

            return 0;
        }

        // Show summary of found placeholders
        $output->newLine();
        $output->section('Found Placeholders');

        $resolver = new PlaceholderResolver();
        $summary  = $resolver->getSummary($registry);

        foreach ($summary as $id => $info) {
            $prodCount = $info['production_count'];
            $testCount = $info['test_count'];
            $linkCount = $info['link_count'];

            $status = $prodCount > 0 && $testCount > 0
                ? $output->green('✓')
                : $output->red('✗');

            $output->writeln("    {$status} {$id}  {$prodCount} production × {$testCount} tests = {$linkCount} links");
        }

        $output->newLine();

        // Resolve placeholders
        if ($placeholder !== null && $placeholder !== '') {
            // Resolve specific placeholder
            if (!PlaceholderRegistry::isPlaceholder($placeholder)) {
                $output->error("Invalid placeholder format: {$placeholder}");
                $output->newLine();

                return 1;
            }

            $result = $resolver->resolvePlaceholder($placeholder, $registry);
        } else {
            // Resolve all placeholders
            $result = $resolver->resolve($registry);
        }

        // Report errors
        if ($result->hasErrors()) {
            $output->section('Errors');

            foreach ($result->errors as $error) {
                $output->error($error);
            }

            $output->newLine();

            return 1;
        }

        if (!$result->hasActions()) {
            $output->success('No actions to perform.');
            $output->newLine();

            return 0;
        }

        // Show what will be modified
        $this->showPendingChanges($result, $output);

        // Apply changes
        $modifier    = new PlaceholderModifier();
        $applyResult = $modifier->apply($result->actions, $dryRun);

        // Report results
        return $this->reportResult($applyResult, $output, $dryRun);
    }

    /**
     * Show pending changes.
     */
    private function showPendingChanges(\TestFlowLabs\TestLink\Placeholder\PlaceholderResult $result, Output $output): void
    {
        $productionFiles = $result->getProductionFilesToModify();
        $testFiles       = $result->getTestFilesToModify();

        if ($productionFiles !== []) {
            $output->section('Production Files');

            foreach ($productionFiles as $file) {
                $output->writeln('    '.$this->shortenPath($file));
                $actions = $result->getActionsForProductionFile($file);

                foreach ($actions as $action) {
                    $output->writeln('      '.$output->cyan($action->placeholderId).' → '.$output->green($this->shortenTestId($action->getTestIdentifier())));
                }
            }

            $output->newLine();
        }

        if ($testFiles !== []) {
            $output->section('Test Files');

            foreach ($testFiles as $file) {
                $output->writeln('    '.$this->shortenPath($file));
                $actions = $result->getActionsForTestFile($file);

                foreach ($actions as $action) {
                    $output->writeln('      '.$output->cyan($action->placeholderId).' → '.$output->green($this->shortenMethod($action->getProductionMethodIdentifier())));
                }
            }

            $output->newLine();
        }
    }

    /**
     * Report results.
     *
     * @param  array{modified_files: list<string>, changes: list<array{file: string, placeholder: string, replacement: string}>}  $result
     */
    private function reportResult(array $result, Output $output, bool $dryRun): int
    {
        $modifiedCount = count($result['modified_files']);
        $changeCount   = count($result['changes']);

        if ($dryRun) {
            $output->info("Dry run complete. Would modify {$modifiedCount} file(s) with {$changeCount} change(s).");
            $output->newLine();
            $output->writeln('    Run without --dry-run to apply changes:');
            $output->writeln('    testlink pair');
        } else {
            $output->success("Pairing complete. Modified {$modifiedCount} file(s) with {$changeCount} change(s).");
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
        if (str_contains($method, '\\')) {
            $parts = explode('\\', $method);

            return array_pop($parts);
        }

        return $method;
    }

    /**
     * Shorten test identifier for display.
     */
    private function shortenTestId(string $testId): string
    {
        // Tests\Unit\UserServiceTest::it creates user → UserServiceTest::it creates user
        if (str_contains($testId, '\\')) {
            $parts = explode('\\', $testId);

            return array_pop($parts);
        }

        return $testId;
    }
}
