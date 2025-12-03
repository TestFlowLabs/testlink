<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Contract;

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;

/**
 * Interface for modifying test files to inject or remove link declarations.
 */
interface TestModifierInterface
{
    /**
     * Inject link declarations into a test.
     *
     * @param  list<string>  $methods  Method identifiers to link (e.g., "Class::method")
     * @param  bool  $withCoverage  If true, use linksAndCovers(); if false, use links()
     *
     * @return string Modified code
     */
    public function injectLinks(
        string $code,
        ParsedTestCase $testCase,
        array $methods,
        bool $withCoverage = true,
    ): string;

    /**
     * Remove link declarations from a test.
     *
     * @param  list<string>  $methods  Method identifiers to remove
     *
     * @return string Modified code
     */
    public function removeLinks(
        string $code,
        ParsedTestCase $testCase,
        array $methods,
    ): string;

    /**
     * Check if this modifier can handle the given test case.
     */
    public function supports(ParsedTestCase $testCase): bool;
}
