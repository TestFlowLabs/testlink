<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Discovery;

use TestFlowLabs\TestLink\Adapter\CompositeAdapter;
use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;
use TestFlowLabs\TestLink\Sync\Exception\TestCaseNotFoundException;

/**
 * Finds test cases within test files.
 *
 * Uses the adapter pattern to support multiple testing frameworks.
 */
final class TestCaseFinder
{
    public function __construct(private readonly CompositeAdapter $compositeAdapter = new CompositeAdapter()) {}

    /**
     * Find a specific test case by name in a file.
     *
     * @throws TestCaseNotFoundException
     */
    public function findTestCase(string $filePath, string $testName): ParsedTestCase
    {
        if (!file_exists($filePath)) {
            throw new TestCaseNotFoundException($testName, $filePath, 'File does not exist');
        }

        $code = file_get_contents($filePath);

        if ($code === false) {
            throw new TestCaseNotFoundException($testName, $filePath, 'Could not read file');
        }

        // Get the appropriate parser for this file
        $adapter = $this->compositeAdapter->getAdapterForFile($filePath);

        if (!$adapter instanceof \TestFlowLabs\TestLink\Contract\FrameworkAdapterInterface) {
            throw new TestCaseNotFoundException($testName, $filePath, 'No parser available for this file type');
        }

        $parser = $adapter->getParser();

        // For parsers that support findTestByName
        if ($parser instanceof \TestFlowLabs\TestLink\Sync\Parser\PestTestParser) {
            $testCase = $parser->findTestByName($code, $testName);

            if ($testCase instanceof \TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase) {
                return $testCase;
            }
        }

        // For parsers that support findTestByName (PHPUnit parser)
        if ($parser instanceof \TestFlowLabs\TestLink\Parser\PhpUnitTestParser) {
            $testCase = $parser->findTestByName($code, $testName);

            if ($testCase instanceof \TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase) {
                return $testCase;
            }
        }

        // Fallback: search all tests
        $allTests = $parser->parseFile($filePath);

        foreach ($allTests as $test) {
            if ($test->name === $testName || $test->getFullName() === $testName) {
                return $test;
            }
        }

        throw new TestCaseNotFoundException($testName, $filePath);
    }

    /**
     * Find all test cases in a file.
     *
     * @return list<ParsedTestCase>
     */
    public function findAllTestCases(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $adapter = $this->compositeAdapter->getAdapterForFile($filePath);

        if (!$adapter instanceof \TestFlowLabs\TestLink\Contract\FrameworkAdapterInterface) {
            return [];
        }

        return $adapter->getParser()->parseFile($filePath);
    }

    /**
     * Check if a test case exists in a file.
     */
    public function testCaseExists(string $filePath, string $testName): bool
    {
        try {
            $this->findTestCase($filePath, $testName);

            return true;
        } catch (TestCaseNotFoundException) {
            return false;
        }
    }

    /**
     * Find test cases that match a pattern.
     *
     * @return list<ParsedTestCase>
     */
    public function findTestCasesMatching(string $filePath, string $pattern): array
    {
        $allTests = $this->findAllTestCases($filePath);

        return array_values(array_filter(
            $allTests,
            fn (ParsedTestCase $test): bool => fnmatch($pattern, $test->getFullName())
        ));
    }
}
