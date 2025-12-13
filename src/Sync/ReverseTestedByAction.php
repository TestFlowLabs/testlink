<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync;

/**
 * Value object representing a reverse sync action (test → production).
 *
 * Used to add #[TestedBy] attributes to production methods
 * based on existing linksAndCovers()/LinksAndCovers in test files.
 */
final readonly class ReverseTestedByAction
{
    public function __construct(
        public string $productionFile,
        public string $methodIdentifier,
        public string $testIdentifier,
        public string $className,
        public string $methodName,
    ) {}

    /**
     * Get the #[TestedBy] representation for display.
     */
    public function getTestedByDisplay(): string
    {
        $testShortClass = $this->getShortClassName($this->getTestClassName());
        $testMethod     = $this->getTestMethodName();

        if ($testMethod !== null) {
            return "#[TestedBy({$testShortClass}::class, '{$testMethod}')]";
        }

        return "#[TestedBy({$testShortClass}::class)]";
    }

    /**
     * Get the test class name from the test identifier.
     */
    public function getTestClassName(): string
    {
        if (str_contains($this->testIdentifier, '::')) {
            [$class] = explode('::', $this->testIdentifier, 2);

            return $class;
        }

        return $this->testIdentifier;
    }

    /**
     * Get the test method name from the test identifier.
     */
    public function getTestMethodName(): ?string
    {
        if (str_contains($this->testIdentifier, '::')) {
            [, $method] = explode('::', $this->testIdentifier, 2);

            return $method;
        }

        return null;
    }

    /**
     * Get short class name from FQCN.
     */
    private function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
