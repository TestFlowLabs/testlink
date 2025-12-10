<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Placeholder;

/**
 * Registry for storing and managing placeholder mappings.
 *
 * Stores placeholder entries from both production code (#[TestedBy('@A')])
 * and test code (linksAndCovers('@A') or #[LinksAndCovers('@A')]).
 */
final class PlaceholderRegistry
{
    /**
     * Regex pattern for valid placeholder identifiers.
     * Must start with @ followed by a letter, then letters, numbers, underscores, or hyphens.
     */
    private const PLACEHOLDER_PATTERN = '/^@[A-Za-z][A-Za-z0-9_-]*$/';

    /**
     * Production placeholder entries indexed by placeholder ID.
     *
     * @var array<string, list<PlaceholderEntry>>
     */
    private array $productionEntries = [];

    /**
     * Test placeholder entries indexed by placeholder ID.
     *
     * @var array<string, list<PlaceholderEntry>>
     */
    private array $testEntries = [];

    /**
     * Check if a string is a valid placeholder identifier.
     *
     * Valid: @A, @B, @user-create, @UserCreate123
     * Invalid: @, @123, @invalid!, UserService::class, App\Services\UserService::create
     */
    public static function isPlaceholder(string $value): bool
    {
        if ($value === '' || $value === '@') {
            return false;
        }

        return preg_match(self::PLACEHOLDER_PATTERN, $value) === 1;
    }

    /**
     * Register a production placeholder entry.
     *
     * @param  string  $placeholder  The placeholder (e.g., '@A')
     * @param  string  $className  The fully qualified class name
     * @param  string  $methodName  The method name
     * @param  string  $filePath  Absolute path to the file
     * @param  int  $line  Line number
     */
    public function registerProductionPlaceholder(
        string $placeholder,
        string $className,
        string $methodName,
        string $filePath,
        int $line,
    ): void {
        $identifier = $className.'::'.$methodName;

        $entry = new PlaceholderEntry(
            placeholder: $placeholder,
            identifier: $identifier,
            filePath: $filePath,
            line: $line,
            type: 'production',
        );

        $this->productionEntries[$placeholder][] = $entry;
    }

    /**
     * Register a test placeholder entry.
     *
     * @param  string  $placeholder  The placeholder (e.g., '@A')
     * @param  string  $testIdentifier  The test identifier (e.g., 'Tests\Unit\UserServiceTest::it creates user')
     * @param  string  $filePath  Absolute path to the test file
     * @param  int  $line  Line number
     * @param  'pest'|'phpunit'  $framework  The test framework
     */
    public function registerTestPlaceholder(
        string $placeholder,
        string $testIdentifier,
        string $filePath,
        int $line,
        string $framework,
    ): void {
        $entry = new PlaceholderEntry(
            placeholder: $placeholder,
            identifier: $testIdentifier,
            filePath: $filePath,
            line: $line,
            type: 'test',
            framework: $framework,
        );

        $this->testEntries[$placeholder][] = $entry;
    }

    /**
     * Get all production entries for a placeholder.
     *
     * @return list<PlaceholderEntry>
     */
    public function getProductionEntries(string $placeholder): array
    {
        return $this->productionEntries[$placeholder] ?? [];
    }

    /**
     * Get all test entries for a placeholder.
     *
     * @return list<PlaceholderEntry>
     */
    public function getTestEntries(string $placeholder): array
    {
        return $this->testEntries[$placeholder] ?? [];
    }

    /**
     * Get all unique placeholder IDs from both production and test entries.
     *
     * @return list<string>
     */
    public function getAllPlaceholderIds(): array
    {
        $ids = array_unique([
            ...array_keys($this->productionEntries),
            ...array_keys($this->testEntries),
        ]);

        sort($ids);

        return $ids;
    }

    /**
     * Get all production entries.
     *
     * @return array<string, list<PlaceholderEntry>>
     */
    public function getAllProductionEntries(): array
    {
        return $this->productionEntries;
    }

    /**
     * Get all test entries.
     *
     * @return array<string, list<PlaceholderEntry>>
     */
    public function getAllTestEntries(): array
    {
        return $this->testEntries;
    }

    /**
     * Check if a placeholder has production entries.
     */
    public function hasProductionEntries(string $placeholder): bool
    {
        return isset($this->productionEntries[$placeholder])
            && count($this->productionEntries[$placeholder]) > 0;
    }

    /**
     * Check if a placeholder has test entries.
     */
    public function hasTestEntries(string $placeholder): bool
    {
        return isset($this->testEntries[$placeholder])
            && count($this->testEntries[$placeholder]) > 0;
    }

    /**
     * Get the total count of production entries.
     */
    public function getProductionEntryCount(): int
    {
        return array_sum(array_map(count(...), $this->productionEntries));
    }

    /**
     * Get the total count of test entries.
     */
    public function getTestEntryCount(): int
    {
        return array_sum(array_map(count(...), $this->testEntries));
    }
}
