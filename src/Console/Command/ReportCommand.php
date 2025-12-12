<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console\Command;

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\DocBlock\SeeTagRegistry;
use TestFlowLabs\TestLink\Validator\LinkValidator;
use TestFlowLabs\TestLink\DocBlock\DocBlockScanner;
use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * Report command - displays coverage links from test files.
 *
 * Shows links from both PHPUnit attributes (#[LinksAndCovers], #[Links]),
 * Pest method chains (linksAndCovers(), links()), and @see tags.
 */
final class ReportCommand
{
    /**
     * Execute the report command.
     */
    public function execute(ArgumentParser $parser, Output $output): int
    {
        $path = $parser->getString('path');

        $attributeRegistry = $this->scanAttributes($path);
        $runtimeRegistry   = TestLinkRegistry::getInstance();

        $validator = new LinkValidator();
        $allLinks  = $validator->getAllLinks($attributeRegistry, $runtimeRegistry);

        // Scan for @see tags
        $seeRegistry = $this->scanSeeTags($path);

        $isJson = $parser->hasOption('json');

        if ($isJson) {
            return $this->outputJson($allLinks, $seeRegistry, $output);
        }

        return $this->outputConsole($allLinks, $seeRegistry, $output);
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
     * Output as JSON.
     *
     * @param  array<string, list<string>>  $allLinks
     */
    private function outputJson(array $allLinks, SeeTagRegistry $seeRegistry, Output $output): int
    {
        $data = [
            'links'   => $allLinks,
            'seeTags' => [
                'production' => $this->formatSeeTags($seeRegistry->getAllProductionSeeTags()),
                'test'       => $this->formatSeeTags($seeRegistry->getAllTestSeeTags()),
            ],
            'summary' => [
                'total_methods'  => count($allLinks),
                'total_tests'    => $this->countUniqueTests($allLinks),
                'see_tags_total' => $seeRegistry->count(),
            ],
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $output->writeln($json !== false ? $json : '{}');

        return 0;
    }

    /**
     * Output to console.
     *
     * @param  array<string, list<string>>  $allLinks
     */
    private function outputConsole(array $allLinks, SeeTagRegistry $seeRegistry, Output $output): int
    {
        $seeCount = $seeRegistry->count();

        if ($allLinks === [] && $seeCount === 0) {
            $output->title('Coverage Links Report');
            $output->warning('No coverage links found.');
            $output->newLine();
            $output->comment('Add coverage links to your test files:');
            $output->newLine();
            $output->writeln('    Pest:    ->linksAndCovers(UserService::class.\'::create\')');
            $output->writeln('    PHPUnit: #[LinksAndCovers(UserService::class, \'create\')]');
            $output->newLine();

            return 0;
        }

        $output->title('Coverage Links Report');

        $totalMethods = count($allLinks);
        $totalTests   = $this->countUniqueTests($allLinks);

        // Group by class
        $byClass = [];

        foreach ($allLinks as $methodIdentifier => $testIdentifiers) {
            if (str_contains($methodIdentifier, '::')) {
                [$class, $method]         = explode('::', $methodIdentifier, 2);
                $byClass[$class][$method] = $testIdentifiers;
            } else {
                $byClass[$methodIdentifier]['(class)'] = $testIdentifiers;
            }
        }

        foreach ($byClass as $class => $methods) {
            $output->section($class);

            foreach ($methods as $method => $tests) {
                $output->writeln('    '.$output->cyan($method.'()'));

                foreach ($tests as $test) {
                    $output->listItem($output->gray($test), '→');
                }
            }
        }

        // Report @see tags if any
        if ($seeCount > 0) {
            $output->section('@see Tags');

            $productionSeeTags = $seeRegistry->getAllProductionSeeTags();
            $testSeeTags       = $seeRegistry->getAllTestSeeTags();

            if ($productionSeeTags !== []) {
                $output->writeln('    '.$output->bold('Production code → Tests:'));

                foreach ($productionSeeTags as $methodId => $entries) {
                    $output->writeln('      '.$output->cyan($this->shortenMethod($methodId)));

                    foreach ($entries as $entry) {
                        $output->writeln('        → '.$output->gray($entry->reference));
                    }
                }

                $output->newLine();
            }

            if ($testSeeTags !== []) {
                $output->writeln('    '.$output->bold('Test code → Production:'));

                foreach ($testSeeTags as $testId => $entries) {
                    $output->writeln('      '.$output->cyan($this->shortenMethod($testId)));

                    foreach ($entries as $entry) {
                        $output->writeln('        → '.$output->gray($entry->reference));
                    }
                }

                $output->newLine();
            }
        }

        $output->newLine();
        $output->writeln($output->bold('  Summary'));
        $output->writeln("    Methods with tests: {$totalMethods}");
        $output->writeln("    Total test links: {$totalTests}");
        $output->writeln("    @see tags: {$seeCount}");
        $output->newLine();

        return 0;
    }

    /**
     * Count unique tests across all methods.
     *
     * @param  array<string, list<string>>  $allLinks
     */
    private function countUniqueTests(array $allLinks): int
    {
        $count = 0;

        foreach ($allLinks as $tests) {
            $count += count($tests);
        }

        return $count;
    }

    /**
     * Format @see tags for JSON output.
     *
     * @param  array<string, list<\TestFlowLabs\TestLink\DocBlock\SeeTagEntry>>  $seeTags
     *
     * @return array<string, list<array{reference: string, file: string, line: int}>>
     */
    private function formatSeeTags(array $seeTags): array
    {
        $result = [];

        foreach ($seeTags as $identifier => $entries) {
            $result[$identifier] = array_map(fn (\TestFlowLabs\TestLink\DocBlock\SeeTagEntry $entry): array => [
                'reference' => $entry->reference,
                'file'      => $entry->filePath,
                'line'      => $entry->line,
            ], $entries);
        }

        return $result;
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
