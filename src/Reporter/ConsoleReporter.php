<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Reporter;

use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * Human-readable console output for coverage links.
 */
final class ConsoleReporter
{
    private const GREEN = "\033[32m";

    private const RED = "\033[31m";

    private const YELLOW = "\033[33m";

    private const RESET = "\033[0m";

    private const BOLD = "\033[1m";

    /** @var resource */
    private $output;

    /**
     * @param  resource|null  $output  Output stream (defaults to STDOUT)
     */
    public function __construct($output = null)
    {
        $this->output = $output ?? STDOUT;
    }

    /**
     * Report all coverage links.
     */
    public function report(TestLinkRegistry $registry): void
    {
        $links = $registry->getAllLinks();

        if ($links === []) {
            $this->writeLine("\n".self::YELLOW.'No coverage links found.'.self::RESET."\n");

            return;
        }

        $this->writeLine("\n".self::BOLD.'Coverage Links Report'.self::RESET);
        $this->writeLine(str_repeat('=', 21)."\n");

        foreach ($links as $method => $tests) {
            $this->writeLine(self::BOLD.$this->formatMethod($method).self::RESET);

            $lastIndex = count($tests) - 1;
            foreach ($tests as $index => $test) {
                $prefix = $index === $lastIndex ? '  └─ ' : '  ├─ ';
                $this->writeLine($prefix.$this->formatTest($test));
            }
            $this->writeLine('');
        }

        $this->writeLine(sprintf(
            'Total: %s%d links%s across %s%d methods%s',
            self::GREEN,
            $registry->count(),
            self::RESET,
            self::GREEN,
            $registry->countMethods(),
            self::RESET,
        ));
        $this->writeLine('');
    }

    /**
     * Report validation results.
     *
     * @param  array{
     *     valid: bool,
     *     attributeLinks: array<string, list<string>>,
     *     runtimeLinks: array<string, list<string>>,
     *     duplicates: list<array{test: string, method: string}>,
     *     totalLinks: int
     * }  $result
     */
    public function reportValidation(array $result): void
    {
        $this->writeLine("\n".self::BOLD.'Coverage Links Validation'.self::RESET);
        $this->writeLine(str_repeat('=', 25)."\n");

        if ($result['valid']) {
            $this->writeLine(self::GREEN.'✓ All coverage links are valid.'.self::RESET."\n");
            $this->writeLine(sprintf('  Total links: %d', $result['totalLinks']));
            $this->writeLine('');

            return;
        }

        // Report duplicate links
        if ($result['duplicates'] !== []) {
            $this->writeLine(self::RED.'Duplicate links found:'.self::RESET."\n");

            foreach ($result['duplicates'] as $item) {
                $this->writeLine('  '.$this->formatTest($item['test']));
                $this->writeLine('    └─ Duplicates: '.$this->formatMethod($item['method']));
            }
            $this->writeLine('');
        }

        $totalIssues = count($result['duplicates']);
        $this->writeLine(sprintf(
            self::RED.'✗ Found %d issue(s).'.self::RESET."\n",
            $totalIssues,
        ));
    }

    /**
     * Format a method identifier for display.
     */
    private function formatMethod(string $method): string
    {
        return self::YELLOW.$method.self::RESET;
    }

    /**
     * Format a test identifier for display.
     */
    private function formatTest(string $test): string
    {
        return self::GREEN.$test.self::RESET;
    }

    /**
     * Write a line to output stream.
     */
    private function writeLine(string $line): void
    {
        fwrite($this->output, $line."\n");
    }
}
