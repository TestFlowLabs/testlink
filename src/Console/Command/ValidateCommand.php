<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console\Command;

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\DocBlock\SeeTagEntry;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\DocBlock\SeeTagRegistry;
use TestFlowLabs\TestLink\Validator\LinkValidator;
use TestFlowLabs\TestLink\DocBlock\DocBlockScanner;
use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Placeholder\PlaceholderScanner;
use TestFlowLabs\TestLink\Placeholder\PlaceholderRegistry;

/**
 * Validate command - validates coverage link synchronization.
 *
 * Checks for duplicate links (same link in both PHPUnit attributes and Pest chains),
 * detects unresolved placeholders, validates @see tags, and reports overall coverage link health.
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

        // Scan for @see tags and find orphans
        $seeRegistry = $this->scanSeeTags($path);
        $seeOrphans  = $this->findSeeOrphans($seeRegistry, $attributeRegistry);

        $isJson   = $parser->hasOption('json');
        $isStrict = $parser->hasOption('strict');

        if ($isJson) {
            return $this->outputJson($result, $placeholderRegistry, $seeRegistry, $seeOrphans, $output);
        }

        return $this->outputConsole($result, $placeholderRegistry, $seeRegistry, $seeOrphans, $output, $isStrict);
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
     * Scan for @see tags in docblocks.
     */
    private function scanSeeTags(?string $path): SeeTagRegistry
    {
        $registry = new SeeTagRegistry();
        $scanner  = new DocBlockScanner();

        if ($path !== null) {
            $scanner->setProjectRoot($path);
        }

        $scanner->scan($registry);

        return $registry;
    }

    /**
     * Find orphan @see tags that point to invalid targets.
     *
     * Uses Reflection to verify that referenced classes and methods actually exist,
     * rather than only checking against TestLink attribute registry.
     *
     * @return list<SeeTagEntry>
     */
    private function findSeeOrphans(SeeTagRegistry $seeRegistry, TestLinkRegistry $attributeRegistry): array
    {
        $orphans = [];

        // Check production @see tags (pointing to test classes/methods)
        foreach ($seeRegistry->getAllProductionSeeTags() as $entries) {
            foreach ($entries as $entry) {
                if (!$this->targetExists($entry->reference)) {
                    $orphans[] = $entry;
                }
            }
        }

        // Check test @see tags (pointing to production classes/methods)
        foreach ($seeRegistry->getAllTestSeeTags() as $entries) {
            foreach ($entries as $entry) {
                if (!$this->targetExists($entry->reference)) {
                    $orphans[] = $entry;
                }
            }
        }

        return $orphans;
    }

    /**
     * Check if a @see target (class::method or class) actually exists.
     *
     * For safety, only checks if the class is already loaded (without triggering autoload).
     * This avoids fatal "cannot redeclare class" errors in projects with complex autoloading.
     * If the class isn't loaded yet, we assume it's valid since we can't safely verify.
     */
    private function targetExists(string $reference): bool
    {
        $normalized = ltrim($reference, '\\');

        // Handle Class::method format
        if (str_contains($normalized, '::')) {
            [$className, $methodName] = explode('::', $normalized, 2);

            // Remove () from method name if present (e.g., "testFoo()")
            $methodName = rtrim($methodName, '()');

            // Only check classes that are already loaded to avoid autoload issues
            // If class isn't loaded, assume it's valid (conservative approach)
            if (!class_exists($className, false)) {
                return true;
            }

            try {
                $reflection = new \ReflectionClass($className);

                return $reflection->hasMethod($methodName);
            } catch (\ReflectionException) {
                return false;
            }
        }

        // Handle class-only reference - assume valid (conservative approach)
        return true;
    }

    /**
     * Output as JSON.
     *
     * @param  array{valid: bool, attributeLinks: array<string, list<string>>, runtimeLinks: array<string, list<string>>, duplicates: list<array{test: string, method: string}>, totalLinks: int}  $result
     * @param  list<SeeTagEntry>  $seeOrphans
     */
    private function outputJson(array $result, PlaceholderRegistry $placeholderRegistry, SeeTagRegistry $seeRegistry, array $seeOrphans, Output $output): int
    {
        $placeholderIds = $placeholderRegistry->getAllPlaceholderIds();

        $unresolvedPlaceholders = array_map(fn (string $id): array => [
            'id'              => $id,
            'productionCount' => count($placeholderRegistry->getProductionEntries($id)),
            'testCount'       => count($placeholderRegistry->getTestEntries($id)),
        ], $placeholderIds);

        $orphanSeeTags = array_map(fn (SeeTagEntry $entry): array => [
            'reference' => $entry->reference,
            'file'      => $entry->filePath,
            'line'      => $entry->line,
            'context'   => $entry->context,
            'method'    => $entry->getMethodIdentifier(),
        ], $seeOrphans);

        $outputData = [
            ...$result,
            'unresolvedPlaceholders' => $unresolvedPlaceholders,
            'seeTags'                => [
                'production' => $seeRegistry->countProduction(),
                'test'       => $seeRegistry->countTest(),
                'total'      => $seeRegistry->count(),
                'orphans'    => $orphanSeeTags,
            ],
        ];

        $json = json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output->writeln($json !== false ? $json : '{}');

        $hasOrphans = $seeOrphans !== [];

        return ($result['valid'] && !$hasOrphans) ? 0 : 1;
    }

    /**
     * Output to console.
     *
     * @param  array{valid: bool, attributeLinks: array<string, list<string>>, runtimeLinks: array<string, list<string>>, duplicates: list<array{test: string, method: string}>, totalLinks: int}  $result
     * @param  list<SeeTagEntry>  $seeOrphans
     */
    private function outputConsole(array $result, PlaceholderRegistry $placeholderRegistry, SeeTagRegistry $seeRegistry, array $seeOrphans, Output $output, bool $strict): int
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

        // Report orphan @see tags
        if ($seeOrphans !== []) {
            $output->section('Orphan @see Tags');
            $output->comment('These @see tags point to targets that no longer exist:');
            $output->newLine();

            foreach ($seeOrphans as $orphan) {
                $location = $this->shortenPath($orphan->filePath).':'.$orphan->line;
                $output->writeln('    '.$output->red('✗').' '.$orphan->reference);
                $output->writeln('      → '.$output->gray($location));
            }

            $output->newLine();
            $output->warning('Run "testlink sync --prune --force" to remove orphan @see tags.');
            $output->newLine();

            $hasErrors = true;
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
        $seeCount       = $seeRegistry->count();

        $output->writeln("    PHPUnit attribute links: {$attributeCount}");
        $output->writeln("    Pest method chain links: {$runtimeCount}");
        $output->writeln("    @see tags: {$seeCount}");
        $output->writeln("    Total links: {$totalLinks}");
        $output->newLine();

        if ($totalLinks === 0 && $seeCount === 0) {
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
}
