<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console\Command;

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\Validator\LinkValidator;
use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Placeholder\PlaceholderScanner;
use TestFlowLabs\TestLink\Placeholder\PlaceholderRegistry;

/**
 * Validate command - validates coverage link synchronization.
 *
 * Checks for duplicate links (same link in both PHPUnit attributes and Pest chains),
 * detects unresolved placeholders, and reports overall coverage link health.
 */
final class ValidateCommand
{
    /**
     * Execute the validate command.
     */
    public function execute(ArgumentParser $parser, Output $output): int
    {
        $path = $parser->getString('path');

        $attributeRegistry = $this->scanAttributes($path);
        $runtimeRegistry   = TestLinkRegistry::getInstance();

        $validator = new LinkValidator();
        $result    = $validator->validate($attributeRegistry, $runtimeRegistry);

        // Scan for unresolved placeholders
        $placeholderRegistry = $this->scanPlaceholders($path);

        $isJson   = $parser->hasOption('json');
        $isStrict = $parser->hasOption('strict');

        if ($isJson) {
            return $this->outputJson($result, $placeholderRegistry, $output);
        }

        return $this->outputConsole($result, $placeholderRegistry, $output, $isStrict);
    }

    /**
     * Scan for #[LinksAndCovers] and #[Links] attributes in test files.
     */
    private function scanAttributes(?string $path): TestLinkRegistry
    {
        $registry = new TestLinkRegistry();
        $scanner  = new AttributeScanner();

        if ($path !== null) {
            $scanner->setProjectRoot($path);
        }

        $scanner->discoverAndScan($registry);

        return $registry;
    }

    /**
     * Scan for unresolved placeholders.
     */
    private function scanPlaceholders(?string $path): PlaceholderRegistry
    {
        $registry = new PlaceholderRegistry();
        $scanner  = new PlaceholderScanner();

        if ($path !== null) {
            $scanner->setProjectRoot($path);
        }

        $scanner->scan($registry);

        return $registry;
    }

    /**
     * Output as JSON.
     *
     * @param  array{valid: bool, attributeLinks: array<string, list<string>>, runtimeLinks: array<string, list<string>>, duplicates: list<array{test: string, method: string}>, totalLinks: int}  $result
     */
    private function outputJson(array $result, PlaceholderRegistry $placeholderRegistry, Output $output): int
    {
        $placeholderIds = $placeholderRegistry->getAllPlaceholderIds();

        $unresolvedPlaceholders = array_map(fn (string $id): array => [
            'id'              => $id,
            'productionCount' => count($placeholderRegistry->getProductionEntries($id)),
            'testCount'       => count($placeholderRegistry->getTestEntries($id)),
        ], $placeholderIds);

        $outputData = [
            ...$result,
            'unresolvedPlaceholders' => $unresolvedPlaceholders,
        ];

        $json = json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output->writeln($json !== false ? $json : '{}');

        return $result['valid'] ? 0 : 1;
    }

    /**
     * Output to console.
     *
     * @param  array{valid: bool, attributeLinks: array<string, list<string>>, runtimeLinks: array<string, list<string>>, duplicates: list<array{test: string, method: string}>, totalLinks: int}  $result
     */
    private function outputConsole(array $result, PlaceholderRegistry $placeholderRegistry, Output $output, bool $strict): int
    {
        $output->title('Validation Report');

        $hasErrors = false;

        // Report duplicates if any
        if ($result['duplicates'] !== []) {
            $output->section('Duplicate Links Found');
            $output->comment('These links exist in both PHPUnit attributes and Pest chains:');
            $output->newLine();

            foreach ($result['duplicates'] as $duplicate) {
                $output->writeln('    '.$output->yellow('!').' '.$duplicate['test']);
                $output->writeln('      → '.$output->gray($duplicate['method']));
            }

            $output->newLine();
            $output->warning('Consider using only one linking method per test.');
            $output->newLine();

            $hasErrors = true;
        }

        // Report unresolved placeholders
        $placeholderIds = $placeholderRegistry->getAllPlaceholderIds();

        if ($placeholderIds !== []) {
            $output->section('Unresolved Placeholders');

            foreach ($placeholderIds as $id) {
                $prodCount = count($placeholderRegistry->getProductionEntries($id));
                $testCount = count($placeholderRegistry->getTestEntries($id));
                $output->writeln('    '.$output->yellow('⚠')." {$id}  ({$prodCount} production, {$testCount} tests)");
            }

            $output->newLine();
            $output->warning('Run "testlink pair" to resolve placeholders.');
            $output->newLine();

            if ($strict) {
                $hasErrors = true;
            }
        }

        // Return early if there are errors
        if ($hasErrors) {
            return 1;
        }

        // Summary
        $output->section('Link Summary');

        $attributeCount = $this->countLinks($result['attributeLinks']);
        $runtimeCount   = $this->countLinks($result['runtimeLinks']);
        $totalLinks     = $result['totalLinks'];

        $output->writeln("    PHPUnit attribute links: {$attributeCount}");
        $output->writeln("    Pest method chain links: {$runtimeCount}");
        $output->writeln("    Total links: {$totalLinks}");
        $output->newLine();

        if ($totalLinks === 0) {
            $output->warning('No coverage links found.');
            $output->newLine();
            $output->comment('Add coverage links to your test files:');
            $output->newLine();
            $output->writeln('    Pest:    ->linksAndCovers(UserService::class.\'::create\')');
            $output->writeln('    PHPUnit: #[LinksAndCovers(UserService::class, \'create\')]');
            $output->newLine();

            return 0;
        }

        $output->success('All links are valid!');
        $output->newLine();

        return 0;
    }

    /**
     * Count total links in a links array.
     *
     * @param  array<string, list<string>>  $links
     */
    private function countLinks(array $links): int
    {
        $count = 0;

        foreach ($links as $methods) {
            $count += count($methods);
        }

        return $count;
    }
}
