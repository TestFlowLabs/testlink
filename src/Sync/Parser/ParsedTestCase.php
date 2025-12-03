<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Parser;

/**
 * Value object representing a parsed test case.
 */
final readonly class ParsedTestCase
{
    public const TYPE_PEST = 'pest';

    public const TYPE_PHPUNIT = 'phpunit';

    /**
     * @param  list<string>  $existingCoversMethod  Already declared linksAndCovers()/links() calls
     * @param  list<string>  $describePath  Path of describe blocks (for nested tests)
     */
    public function __construct(
        public string $name,
        public string $type,
        public int $startLine,
        public int $endLine,
        public array $existingCoversMethod = [],
        public array $describePath = [],
    ) {}

    /**
     * Get the full test name including describe path.
     */
    public function getFullName(): string
    {
        if ($this->describePath === []) {
            return $this->name;
        }

        return implode(' > ', [...$this->describePath, $this->name]);
    }

    /**
     * Check if this test already has a linksAndCovers/links for the given method.
     */
    public function hasCoversMethod(string $method): bool
    {
        return in_array($method, $this->existingCoversMethod, true);
    }

    /**
     * Check if this is a Pest test.
     */
    public function isPest(): bool
    {
        return $this->type === self::TYPE_PEST;
    }

    /**
     * Check if this is a PHPUnit test.
     */
    public function isPhpUnit(): bool
    {
        return $this->type === self::TYPE_PHPUNIT;
    }
}
