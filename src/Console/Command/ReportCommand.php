<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console\Command;

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\Validator\LinkValidator;
use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * Report command - displays coverage links from test files.
 *
 * Shows links from both PHPUnit attributes (#[LinksAndCovers], #[Links])
 * and Pest method chains (linksAndCovers(), links()).
 */
final class ReportCommand
{
    /**
     * Execute the report command.
     */
    public function execute(ArgumentParser $parser, Output $output): int
    {
        $attributeRegistry = $this->scanAttributes($parser->getString('path'));
        $runtimeRegistry   = TestLinkRegistry::getInstance();

        $validator = new LinkValidator();
        $allLinks  = $validator->getAllLinks($attributeRegistry, $runtimeRegistry);

        $isJson = $parser->hasOption('json');

        if ($isJson) {
            return $this->outputJson($allLinks, $output);
        }

        return $this->outputConsole($allLinks, $output);
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
     * Output as JSON.
     *
     * @param  array<string, list<string>>  $allLinks
     */
    private function outputJson(array $allLinks, Output $output): int
    {
        $data = [
            'links'   => $allLinks,
            'summary' => [
                'total_methods' => count($allLinks),
                'total_tests'   => $this->countUniqueTests($allLinks),
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
    private function outputConsole(array $allLinks, Output $output): int
    {
        if ($allLinks === []) {
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

        $output->newLine();
        $output->writeln($output->bold('  Summary'));
        $output->writeln("    Methods with tests: {$totalMethods}");
        $output->writeln("    Total test links: {$totalTests}");
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
}
