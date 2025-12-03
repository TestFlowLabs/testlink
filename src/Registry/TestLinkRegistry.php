<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Registry;

/**
 * Stores bidirectional mappings between tests and production methods.
 *
 * Provides efficient lookup in both directions:
 * - Find all tests that cover a specific method
 * - Find all methods covered by a specific test
 *
 * Supports two types of links:
 * - With coverage: Link + triggers PHPUnit/Pest coverage (linksAndCovers)
 * - Without coverage: Link only for traceability (links)
 *
 * Also stores TestedBy links from production code for bidirectional validation.
 */
final class TestLinkRegistry
{
    private static ?self $instance = null;

    /** @var array<string, list<string>> method -> tests (from test files) */
    private array $methodToTests = [];

    /** @var array<string, list<string>> test -> methods (from test files) */
    private array $testToMethods = [];

    /** @var array<string, bool> Tracks which links have coverage enabled (key: "test::method") */
    private array $coverageFlags = [];

    /** @var array<string, list<string>> method -> tests (from #[TestedBy] on production code) */
    private array $testedByLinks = [];

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Reset the singleton instance (for testing).
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Register a link between a test and a production method.
     *
     * @param  string  $testIdentifier  Format: "TestClass::testName" or "file.php::test name"
     * @param  string  $methodIdentifier  Format: "ClassName::methodName"
     * @param  bool  $withCoverage  If true, this link also triggers code coverage
     */
    public function registerLink(string $testIdentifier, string $methodIdentifier, bool $withCoverage = true): void
    {
        // Add to method -> tests mapping (avoid duplicates)
        if (!isset($this->methodToTests[$methodIdentifier])) {
            $this->methodToTests[$methodIdentifier] = [];
        }
        if (!in_array($testIdentifier, $this->methodToTests[$methodIdentifier], true)) {
            $this->methodToTests[$methodIdentifier][] = $testIdentifier;
        }

        // Add to test -> methods mapping (avoid duplicates)
        if (!isset($this->testToMethods[$testIdentifier])) {
            $this->testToMethods[$testIdentifier] = [];
        }
        if (!in_array($methodIdentifier, $this->testToMethods[$testIdentifier], true)) {
            $this->testToMethods[$testIdentifier][] = $methodIdentifier;
        }

        // Store coverage flag
        $linkKey                       = $testIdentifier.'::'.$methodIdentifier;
        $this->coverageFlags[$linkKey] = $withCoverage;
    }

    /**
     * Check if a specific link has coverage enabled.
     */
    public function hasLinkCoverage(string $testIdentifier, string $methodIdentifier): bool
    {
        $linkKey = $testIdentifier.'::'.$methodIdentifier;

        return $this->coverageFlags[$linkKey] ?? true;
    }

    /**
     * Get all methods linked by a test that have coverage enabled.
     *
     * @return list<string>
     */
    public function getCoverageMethodsForTest(string $testIdentifier): array
    {
        $methods = $this->getMethodsForTest($testIdentifier);

        return array_values(array_filter(
            $methods,
            fn (string $method): bool => $this->hasLinkCoverage($testIdentifier, $method)
        ));
    }

    /**
     * Get all methods linked by a test without coverage (link only).
     *
     * @return list<string>
     */
    public function getLinkOnlyMethodsForTest(string $testIdentifier): array
    {
        $methods = $this->getMethodsForTest($testIdentifier);

        return array_values(array_filter(
            $methods,
            fn (string $method): bool => !$this->hasLinkCoverage($testIdentifier, $method)
        ));
    }

    /**
     * Get all tests that cover a specific method.
     *
     * @param  string  $methodIdentifier  Format: "ClassName::methodName"
     *
     * @return list<string> List of test identifiers
     */
    public function getTestsForMethod(string $methodIdentifier): array
    {
        return $this->methodToTests[$methodIdentifier] ?? [];
    }

    /**
     * Get all methods covered by a specific test.
     *
     * @param  string  $testIdentifier  Format: "TestClass::testName" or "file.php::test name"
     *
     * @return list<string> List of method identifiers
     */
    public function getMethodsForTest(string $testIdentifier): array
    {
        return $this->testToMethods[$testIdentifier] ?? [];
    }

    /**
     * Get all registered links grouped by method.
     *
     * @return array<string, list<string>> method -> tests mapping
     */
    public function getAllLinks(): array
    {
        return $this->methodToTests;
    }

    /**
     * Get all registered links grouped by test.
     *
     * @return array<string, list<string>> test -> methods mapping
     */
    public function getAllLinksByTest(): array
    {
        return $this->testToMethods;
    }

    /**
     * Check if a method has any registered tests.
     */
    public function hasMethod(string $methodIdentifier): bool
    {
        return isset($this->methodToTests[$methodIdentifier]);
    }

    /**
     * Check if a test has any registered methods.
     */
    public function hasTest(string $testIdentifier): bool
    {
        return isset($this->testToMethods[$testIdentifier]);
    }

    /**
     * Get all registered method identifiers.
     *
     * @return list<string>
     */
    public function getAllMethods(): array
    {
        return array_keys($this->methodToTests);
    }

    /**
     * Get all registered test identifiers.
     *
     * @return list<string>
     */
    public function getAllTests(): array
    {
        return array_keys($this->testToMethods);
    }

    /**
     * Register a TestedBy link from production code.
     *
     * @param  string  $methodIdentifier  Format: "ClassName::methodName"
     * @param  string  $testIdentifier  Format: "TestClass::testName"
     */
    public function registerTestedBy(string $methodIdentifier, string $testIdentifier): void
    {
        if (!isset($this->testedByLinks[$methodIdentifier])) {
            $this->testedByLinks[$methodIdentifier] = [];
        }

        if (!in_array($testIdentifier, $this->testedByLinks[$methodIdentifier], true)) {
            $this->testedByLinks[$methodIdentifier][] = $testIdentifier;
        }
    }

    /**
     * Get all TestedBy links (from production code).
     *
     * @return array<string, list<string>> method -> tests mapping
     */
    public function getTestedByLinks(): array
    {
        return $this->testedByLinks;
    }

    /**
     * Get tests registered via TestedBy for a specific method.
     *
     * @return list<string>
     */
    public function getTestedByForMethod(string $methodIdentifier): array
    {
        return $this->testedByLinks[$methodIdentifier] ?? [];
    }

    /**
     * Check if a method has any TestedBy links.
     */
    public function hasTestedBy(string $methodIdentifier): bool
    {
        return isset($this->testedByLinks[$methodIdentifier]);
    }

    /**
     * Clear all registered links.
     */
    public function clear(): void
    {
        $this->methodToTests = [];
        $this->testToMethods = [];
        $this->coverageFlags = [];
        $this->testedByLinks = [];
    }

    /**
     * Get the total number of unique links.
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->methodToTests as $tests) {
            $count += count($tests);
        }

        return $count;
    }

    /**
     * Get the number of unique methods with links.
     */
    public function countMethods(): int
    {
        return count($this->methodToTests);
    }

    /**
     * Get the number of unique tests with links.
     */
    public function countTests(): int
    {
        return count($this->testToMethods);
    }
}
