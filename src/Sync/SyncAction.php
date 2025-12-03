<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync;

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;

/**
 * Value object representing a single sync action.
 */
final readonly class SyncAction
{
    /**
     * @param  list<string>  $methodsToAdd
     */
    public function __construct(
        public string $testFile,
        public string $testIdentifier,
        public string $testName,
        public string $methodIdentifier,
        public array $methodsToAdd,
        public ?ParsedTestCase $testCase = null,
    ) {}

    /**
     * Get formatted method reference for display.
     */
    public function getFormattedMethod(): string
    {
        if (!str_contains($this->methodIdentifier, '::')) {
            return $this->methodIdentifier.'::class';
        }

        [$class, $method] = explode('::', $this->methodIdentifier, 2);
        $shortClass       = $this->getShortClassName($class);

        return "{$shortClass}::class.'::{$method}'";
    }

    /**
     * Get the linksAndCovers() representation for display (Pest).
     */
    public function getPestLinkDisplay(): string
    {
        return "->linksAndCovers({$this->getFormattedMethod()})";
    }

    /**
     * Get the #[LinksAndCovers] representation for display (PHPUnit).
     */
    public function getPhpUnitLinkDisplay(): string
    {
        if (!str_contains($this->methodIdentifier, '::')) {
            $shortClass = $this->getShortClassName($this->methodIdentifier);

            return "#[LinksAndCovers({$shortClass}::class)]";
        }

        [$class, $method] = explode('::', $this->methodIdentifier, 2);
        $shortClass       = $this->getShortClassName($class);

        return "#[LinksAndCovers({$shortClass}::class, '{$method}')]";
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
