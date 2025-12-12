<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console\Command;

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\DocBlock\SeeTagEntry;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\DocBlock\FqcnValidator;
use TestFlowLabs\TestLink\DocBlock\SeeTagRegistry;
use TestFlowLabs\TestLink\Scanner\PestLinkScanner;
use TestFlowLabs\TestLink\Validator\LinkValidator;
use TestFlowLabs\TestLink\DocBlock\DocBlockScanner;
use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\DocBlock\FqcnIssueRegistry;
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
        $pestRegistry      = $this->scanPestLinks($path);

        $validator = new LinkValidator();
        $result    = $validator->validate($attributeRegistry, $pestRegistry);

        // Scan for unresolved placeholders
        $placeholderRegistry = $this->scanPlaceholders($path);

        // Scan for @see tags and find orphans
        $seeRegistry = $this->scanSeeTags($path);
        $seeOrphans  = $this->findSeeOrphans($seeRegistry, $attributeRegistry, $pestRegistry);

        // Validate @see tags for FQCN format
        $fqcnValidator = new FqcnValidator();
        $fqcnIssues    = $fqcnValidator->validate($seeRegistry);

        $isJson   = $parser->hasOption('json');
        $isStrict = $parser->hasOption('strict');
        $isFix    = $parser->hasOption('fix');
        $isDryRun = $parser->hasOption('dry-run');

        // Handle --fix mode
        $fixResult = null;

        if ($isFix && $fqcnIssues->hasIssues()) {
            $fixResult = $fqcnValidator->fix($fqcnIssues, $isDryRun);
        }

        if ($isJson) {
            return $this->outputJson($result, $placeholderRegistry, $seeRegistry, $seeOrphans, $fqcnIssues, $fixResult, $output);
        }

        return $this->outputConsole($result, $placeholderRegistry, $seeRegistry, $seeOrphans, $fqcnIssues, $fixResult, $output, $isStrict, $isDryRun);
    }

    /**
     * Scan for #[LinksAndCovers], #[Links] in test files and #[TestedBy] in production files.
     */
    private function scanAttributes(?string $path): TestLinkRegistry
    {
        $registry = new TestLinkRegistry();
        $scanner  = new AttributeScanner();

        if ($path !== null) {
            $scanner->setProjectRoot($path);
        }

        $scanner->discoverAndScanAll($registry);

        return $registry;
    }

    /**
     * Scan Pest test files for linksAndCovers() and links() method chains.
     */
    private function scanPestLinks(?string $path): TestLinkRegistry
    {
        $registry = new TestLinkRegistry();
        $scanner  = new PestLinkScanner();

        if ($path !== null) {
            $scanner->setProjectRoot($path);
        }

        $scanner->scan($registry);

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
     * For production @see tags (pointing to tests):
     * - Checks against TestLinkRegistry for Pest tests (they don't have real methods)
     * - Falls back to Reflection for PHPUnit tests
     *
     * For test @see tags (pointing to production):
     * - Uses Reflection to verify production classes/methods exist
     *
     * @return list<SeeTagEntry>
     */
    private function findSeeOrphans(SeeTagRegistry $seeRegistry, TestLinkRegistry $attributeRegistry, TestLinkRegistry $pestRegistry): array
    {
        $orphans = [];

        // Build set of known test identifiers from registries
        $knownTests = $this->buildKnownTestIdentifiers($attributeRegistry, $pestRegistry);

        // Check production @see tags (pointing to test classes/methods)
        foreach ($seeRegistry->getAllProductionSeeTags() as $entries) {
            foreach ($entries as $entry) {
                if (!$this->testTargetExists($entry->reference, $knownTests)) {
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
     * Build a set of known test identifiers from registries.
     *
     * @return array<string, true>
     */
    private function buildKnownTestIdentifiers(TestLinkRegistry $attributeRegistry, TestLinkRegistry $pestRegistry): array
    {
        $knownTests = [];

        // From attribute registry (test identifiers from #[LinksAndCovers] etc.)
        foreach ($attributeRegistry->getAllLinksByTest() as $testIdentifier => $methods) {
            $normalized                = ltrim($testIdentifier, '\\');
            $knownTests[$normalized]   = true;
            $knownTests[$testIdentifier] = true;
        }

        // From attribute registry (test identifiers from #[TestedBy])
        foreach ($attributeRegistry->getTestedByLinks() as $methodId => $testIds) {
            foreach ($testIds as $testIdentifier) {
                $normalized                = ltrim($testIdentifier, '\\');
                $knownTests[$normalized]   = true;
                $knownTests[$testIdentifier] = true;
            }
        }

        // From Pest registry
        foreach ($pestRegistry->getAllLinksByTest() as $testIdentifier => $methods) {
            $normalized                = ltrim($testIdentifier, '\\');
            $knownTests[$normalized]   = true;
            $knownTests[$testIdentifier] = true;
        }

        return $knownTests;
    }

    /**
     * Check if a test target exists.
     *
     * First checks against known test identifiers from registries (for Pest tests),
     * then falls back to Reflection (for PHPUnit tests with real methods).
     *
     * @param  array<string, true>  $knownTests
     */
    private function testTargetExists(string $reference, array $knownTests): bool
    {
        $normalized = ltrim($reference, '\\');

        // Check against known test identifiers (works for Pest tests)
        if (isset($knownTests[$normalized]) || isset($knownTests[$reference])) {
            return true;
        }

        // Fall back to Reflection for PHPUnit tests
        return $this->targetExists($reference);
    }

    /**
     * Check if a @see target (class::method or class) actually exists.
     *
     * Uses ReflectionClass which triggers autoload to verify the target exists.
     * Returns false for non-existent classes or methods.
     */
    private function targetExists(string $reference): bool
    {
        $normalized = ltrim($reference, '\\');

        // Handle Class::method format
        if (str_contains($normalized, '::')) {
            [$className, $methodName] = explode('::', $normalized, 2);

            // Remove () from method name if present (e.g., "testFoo()")
            $methodName = rtrim($methodName, '()');

            try {
                // ReflectionClass triggers autoload automatically
                // If class doesn't exist, it throws ReflectionException
                $reflection = new \ReflectionClass($className);

                return $reflection->hasMethod($methodName);
            } catch (\ReflectionException) {
                // Class doesn't exist or couldn't be loaded
                return false;
            }
        }

        // Handle class-only reference
        try {
            new \ReflectionClass($normalized);

            return true;
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * Output as JSON.
     *
     * @param  array{valid: bool, attributeLinks: array<string, list<string>>, runtimeLinks: array<string, list<string>>, duplicates: list<array{test: string, method: string}>, totalLinks: int}  $result
     * @param  list<SeeTagEntry>  $seeOrphans
     * @param  array{fixed: int, files: array<string, list<string>>, errors: list<string>}|null  $fixResult
     */
    private function outputJson(array $result, PlaceholderRegistry $placeholderRegistry, SeeTagRegistry $seeRegistry, array $seeOrphans, FqcnIssueRegistry $fqcnIssues, ?array $fixResult, Output $output): int
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

        // Build FQCN issues array
        $fqcnIssueList = [];

        foreach ($fqcnIssues->getAllByFile() as $filePath => $issues) {
            foreach ($issues as $issue) {
                $fqcnIssueList[] = [
                    'reference' => $issue->originalReference,
                    'resolved'  => $issue->resolvedFqcn,
                    'file'      => $filePath,
                    'line'      => $issue->line,
                    'context'   => $issue->context,
                    'method'    => $issue->getMethodIdentifier(),
                    'fixable'   => $issue->isFixable(),
                    'error'     => $issue->errorMessage,
                ];
            }
        }

        $outputData = [
            ...$result,
            'unresolvedPlaceholders' => $unresolvedPlaceholders,
            'seeTags'                => [
                'production' => $seeRegistry->countProduction(),
                'test'       => $seeRegistry->countTest(),
                'total'      => $seeRegistry->count(),
                'orphans'    => $orphanSeeTags,
            ],
            'fqcnIssues' => [
                'total'   => $fqcnIssues->count(),
                'fixable' => $fqcnIssues->countFixable(),
                'issues'  => $fqcnIssueList,
            ],
        ];

        // Add fix result if available
        if ($fixResult !== null) {
            $outputData['fqcnFix'] = [
                'fixed'  => $fixResult['fixed'],
                'files'  => $fixResult['files'],
                'errors' => $fixResult['errors'],
            ];
        }

        $json = json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output->writeln($json !== false ? $json : '{}');

        $hasOrphans    = $seeOrphans !== [];
        $hasFqcnIssues = $fqcnIssues->hasIssues();

        return ($result['valid'] && !$hasOrphans && !$hasFqcnIssues) ? 0 : 1;
    }

    /**
     * Output to console.
     *
     * @param  array{valid: bool, attributeLinks: array<string, list<string>>, runtimeLinks: array<string, list<string>>, duplicates: list<array{test: string, method: string}>, totalLinks: int}  $result
     * @param  list<SeeTagEntry>  $seeOrphans
     * @param  array{fixed: int, files: array<string, list<string>>, errors: list<string>}|null  $fixResult
     */
    private function outputConsole(array $result, PlaceholderRegistry $placeholderRegistry, SeeTagRegistry $seeRegistry, array $seeOrphans, FqcnIssueRegistry $fqcnIssues, ?array $fixResult, Output $output, bool $strict, bool $isDryRun): int
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

        // Report FQCN issues or fix results
        if ($fixResult !== null) {
            $this->outputFqcnFixResults($fixResult, $fqcnIssues, $output, $isDryRun);
            // After fix, only unfixable issues count as errors
            $unfixableIssues = $fqcnIssues->getUnfixableIssues();

            if ($unfixableIssues !== []) {
                $hasErrors = true;
            }
        } elseif ($fqcnIssues->hasIssues()) {
            $this->outputFqcnIssues($fqcnIssues, $output);
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
     * Output FQCN validation issues.
     */
    private function outputFqcnIssues(FqcnIssueRegistry $fqcnIssues, Output $output): void
    {
        $output->section('Non-FQCN @see Tags');
        $output->comment('These @see tags should use fully qualified class names:');
        $output->newLine();

        foreach ($fqcnIssues->getAllByFile() as $filePath => $issues) {
            $output->writeln('    '.$this->shortenPath($filePath));

            foreach ($issues as $issue) {
                $icon = $issue->isFixable()
                    ? $output->yellow('!')
                    : $output->red('✗');

                $output->writeln("      {$icon} Line {$issue->line}: {$issue->originalReference}");

                if ($issue->isFixable()) {
                    $output->writeln('        → '.$output->green((string) $issue->resolvedFqcn));
                } else {
                    $output->writeln('        → '.$output->gray((string) $issue->errorMessage));
                }
            }
        }

        $output->newLine();

        if ($fqcnIssues->countFixable() > 0) {
            $output->warning('Run "testlink validate --fix" to convert to FQCN format.');
        }

        $output->newLine();
    }

    /**
     * Output FQCN fix results.
     *
     * @param  array{fixed: int, files: array<string, list<string>>, errors: list<string>}  $fixResult
     */
    private function outputFqcnFixResults(array $fixResult, FqcnIssueRegistry $fqcnIssues, Output $output, bool $isDryRun): void
    {
        $prefix = $isDryRun ? '[DRY-RUN] ' : '';

        if ($fixResult['fixed'] > 0) {
            $output->section("{$prefix}FQCN Conversion Results");

            foreach ($fixResult['files'] as $filePath => $changes) {
                $output->writeln('    '.$output->green('✓').' '.$this->shortenPath($filePath));

                foreach ($changes as $change) {
                    $output->writeln('      + '.$change);
                }
            }

            $output->newLine();

            $fileCount = count($fixResult['files']);
            $output->success("{$prefix}Converted {$fixResult['fixed']} @see tag(s) in {$fileCount} file(s).");
            $output->newLine();
        }

        // Report unfixable issues
        $unfixableIssues = $fqcnIssues->getUnfixableIssues();

        if ($unfixableIssues !== []) {
            $output->section('Unfixable @see Tags');

            foreach ($unfixableIssues as $issue) {
                $location = $this->shortenPath($issue->filePath).':'.$issue->line;
                $output->writeln('    '.$output->red('✗').' '.$issue->originalReference);
                $output->writeln('      → '.$output->gray((string) $issue->errorMessage));
                $output->writeln('      → '.$output->gray($location));
            }

            $output->newLine();
        }

        // Report errors
        if ($fixResult['errors'] !== []) {
            $output->section('Fix Errors');

            foreach ($fixResult['errors'] as $error) {
                $output->writeln('    '.$output->red('✗').' '.$error);
            }

            $output->newLine();
        }
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
