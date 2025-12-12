<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

/**
 * Registry for discovered @see tags across the codebase.
 *
 * Stores @see tags found in both production and test files,
 * enabling duplicate detection and orphan validation.
 */
final class SeeTagRegistry
{
    /** @var array<string, list<SeeTagEntry>> method identifier -> @see entries */
    private array $productionSeeTags = [];

    /** @var array<string, list<SeeTagEntry>> test identifier -> @see entries */
    private array $testSeeTags = [];

    /**
     * Register a @see tag found in production code.
     *
     * @param  string  $methodIdentifier  Format: "ClassName::methodName"
     */
    public function registerProductionSee(string $methodIdentifier, SeeTagEntry $entry): void
    {
        if (!isset($this->productionSeeTags[$methodIdentifier])) {
            $this->productionSeeTags[$methodIdentifier] = [];
        }

        $this->productionSeeTags[$methodIdentifier][] = $entry;
    }

    /**
     * Register a @see tag found in test code.
     *
     * @param  string  $testIdentifier  Format: "TestClass::testMethod" or "file::test name"
     */
    public function registerTestSee(string $testIdentifier, SeeTagEntry $entry): void
    {
        if (!isset($this->testSeeTags[$testIdentifier])) {
            $this->testSeeTags[$testIdentifier] = [];
        }

        $this->testSeeTags[$testIdentifier][] = $entry;
    }

    /**
     * Get all @see entries for a production method.
     *
     * @return list<SeeTagEntry>
     */
    public function getProductionSeeFor(string $methodIdentifier): array
    {
        return $this->productionSeeTags[$methodIdentifier] ?? [];
    }

    /**
     * Get all @see entries for a test.
     *
     * @return list<SeeTagEntry>
     */
    public function getTestSeeFor(string $testIdentifier): array
    {
        return $this->testSeeTags[$testIdentifier] ?? [];
    }

    /**
     * Check if a production method has a @see tag for a specific reference.
     *
     * @param  string  $reference  The @see target (normalized, without leading backslash)
     */
    public function hasProductionSee(string $methodIdentifier, string $reference): bool
    {
        $normalizedRef = ltrim($reference, '\\');

        foreach ($this->getProductionSeeFor($methodIdentifier) as $entry) {
            if ($entry->getNormalizedReference() === $normalizedRef) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a test has a @see tag for a specific reference.
     *
     * @param  string  $reference  The @see target (normalized, without leading backslash)
     */
    public function hasTestSee(string $testIdentifier, string $reference): bool
    {
        $normalizedRef = ltrim($reference, '\\');

        foreach ($this->getTestSeeFor($testIdentifier) as $entry) {
            if ($entry->getNormalizedReference() === $normalizedRef) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all @see entries grouped by production method.
     *
     * @return array<string, list<SeeTagEntry>>
     */
    public function getAllProductionSeeTags(): array
    {
        return $this->productionSeeTags;
    }

    /**
     * Get all @see entries grouped by test.
     *
     * @return array<string, list<SeeTagEntry>>
     */
    public function getAllTestSeeTags(): array
    {
        return $this->testSeeTags;
    }

    /**
     * Get all @see entries (both production and test).
     *
     * @return list<SeeTagEntry>
     */
    public function getAllEntries(): array
    {
        $entries = [];

        foreach ($this->productionSeeTags as $entryList) {
            foreach ($entryList as $entry) {
                $entries[] = $entry;
            }
        }

        foreach ($this->testSeeTags as $entryList) {
            foreach ($entryList as $entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Find orphan @see tags (targets that don't exist).
     *
     * @param  array<string>  $validProductionMethods  List of valid production method identifiers
     * @param  array<string>  $validTestMethods  List of valid test identifiers
     *
     * @return list<SeeTagEntry>
     */
    public function findOrphans(array $validProductionMethods, array $validTestMethods): array
    {
        $orphans = [];

        // Check production @see tags (should point to valid tests)
        foreach ($this->productionSeeTags as $entries) {
            foreach ($entries as $entry) {
                if (!$entry->hasValidTarget($validTestMethods)) {
                    $orphans[] = $entry;
                }
            }
        }

        // Check test @see tags (should point to valid production methods)
        foreach ($this->testSeeTags as $entries) {
            foreach ($entries as $entry) {
                if (!$entry->hasValidTarget($validProductionMethods)) {
                    $orphans[] = $entry;
                }
            }
        }

        return $orphans;
    }

    /**
     * Count total @see tags.
     */
    public function count(): int
    {
        $count = 0;

        foreach ($this->productionSeeTags as $entries) {
            $count += count($entries);
        }

        foreach ($this->testSeeTags as $entries) {
            $count += count($entries);
        }

        return $count;
    }

    /**
     * Count production @see tags.
     */
    public function countProduction(): int
    {
        $count = 0;

        foreach ($this->productionSeeTags as $entries) {
            $count += count($entries);
        }

        return $count;
    }

    /**
     * Count test @see tags.
     */
    public function countTest(): int
    {
        $count = 0;

        foreach ($this->testSeeTags as $entries) {
            $count += count($entries);
        }

        return $count;
    }

    /**
     * Clear all registered @see tags.
     */
    public function clear(): void
    {
        $this->productionSeeTags = [];
        $this->testSeeTags       = [];
    }
}
