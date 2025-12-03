<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Contract;

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;

/**
 * Interface for parsing test files to find test cases.
 */
interface TestParserInterface
{
    /**
     * Parse a test file and return all test cases.
     *
     * @return list<ParsedTestCase>
     */
    public function parseFile(string $filePath): array;

    /**
     * Find a specific test case by name in the given code.
     */
    public function findTestByName(string $code, string $testName): ?ParsedTestCase;

    /**
     * Find all test cases in the given code.
     *
     * @return list<ParsedTestCase>
     */
    public function findAllTests(string $code): array;

    /**
     * Check if this parser can handle the given file.
     */
    public function supports(string $filePath): bool;
}
