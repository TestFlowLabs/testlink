<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Runtime;

use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * Adds links() and linksAndCovers() methods to Pest test cases.
 *
 * This trait is registered via RuntimeBootstrap::init() or Plugin::uses()
 * and adds methods that allow tests to declare which production methods they cover.
 *
 * Usage in Pest:
 *   test('creates user')
 *       ->linksAndCovers(UserService::class.'::create');  // Link + Coverage
 *
 *   test('creates user integration')
 *       ->links(UserService::class.'::create');  // Link only
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait TestLinkTrait
{
    /** @var list<string> Methods linked by this test */
    private array $linkedMethods = [];

    /** @var list<string> Methods linked with coverage by this test */
    private array $linkedWithCoverageMethods = [];

    /**
     * Link this test to production method(s) AND trigger coverage.
     *
     * This creates a traceability link and also includes the method
     * in PHPUnit/Pest code coverage reporting.
     *
     * @param  string  ...$methods  Format: "ClassName::methodName" or "ClassName"
     *
     * @return $this
     */
    public function linksAndCovers(string ...$methods): self
    {
        $registry       = TestLinkRegistry::getInstance();
        $testIdentifier = $this->getTestLinkIdentifier();

        foreach ($methods as $method) {
            $this->linkedWithCoverageMethods[] = $method;
            $registry->registerLink($testIdentifier, $method, withCoverage: true);
        }

        return $this;
    }

    /**
     * Link this test to production method(s) for traceability only.
     *
     * This creates a traceability link but does NOT include the method
     * in code coverage reporting. Useful for integration/e2e tests
     * where coverage is already captured by unit tests.
     *
     * @param  string  ...$methods  Format: "ClassName::methodName" or "ClassName"
     *
     * @return $this
     */
    public function links(string ...$methods): self
    {
        $registry       = TestLinkRegistry::getInstance();
        $testIdentifier = $this->getTestLinkIdentifier();

        foreach ($methods as $method) {
            $this->linkedMethods[] = $method;
            $registry->registerLink($testIdentifier, $method, withCoverage: false);
        }

        return $this;
    }

    /**
     * Get all methods linked by this test (both with and without coverage).
     *
     * @return list<string>
     */
    public function getLinkedMethods(): array
    {
        return [...$this->linkedMethods, ...$this->linkedWithCoverageMethods];
    }

    /**
     * Get methods linked with coverage.
     *
     * @return list<string>
     */
    public function getLinkedWithCoverageMethods(): array
    {
        return $this->linkedWithCoverageMethods;
    }

    /**
     * Get methods linked without coverage (link only).
     *
     * @return list<string>
     */
    public function getLinkOnlyMethods(): array
    {
        return $this->linkedMethods;
    }

    /**
     * Build the test identifier for registry.
     */
    private function getTestLinkIdentifier(): string
    {
        // For Pest tests, name() returns the test description
        // For PHPUnit tests, getName() returns the method name (deprecated in PHPUnit 10+)
        if (method_exists($this, 'name')) {
            $testName = $this->name();
        } elseif (method_exists($this, 'getName')) {
            $testName = $this->getName();
        } else {
            $testName = 'unknown';
        }

        return static::class.'::'.$testName;
    }
}
