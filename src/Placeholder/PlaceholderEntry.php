<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Placeholder;

/**
 * Value object representing a placeholder entry found in production or test code.
 *
 * A placeholder is a temporary marker (like @A, @user-create) that links
 * production methods to tests before the actual class::method identifiers are known.
 *
 * Placeholders can use two prefixes:
 * - `@` (single): Resolves to attributes (#[TestedBy], #[LinksAndCovers], ->linksAndCovers())
 * - `@@` (double): Resolves to @see tags (PHPUnit only, not supported in Pest)
 */
final readonly class PlaceholderEntry
{
    /**
     * @param  string  $placeholder  The placeholder identifier (e.g., '@A', '@@user-create')
     * @param  string  $identifier  The full identifier (e.g., 'App\Services\UserService::create' or 'Tests\Unit\UserServiceTest::it creates user')
     * @param  string  $filePath  Absolute path to the file containing the placeholder
     * @param  int  $line  Line number where the placeholder is defined
     * @param  'production'|'test'  $type  Whether this entry is from production or test code
     * @param  'pest'|'phpunit'|null  $framework  The test framework (null for production entries)
     * @param  bool  $useSeeTag  Whether to use @see tag instead of attribute (@@prefix)
     */
    public function __construct(
        public string $placeholder,
        public string $identifier,
        public string $filePath,
        public int $line,
        public string $type,
        public ?string $framework = null,
        public bool $useSeeTag = false,
    ) {}

    /**
     * Check if this is a production entry.
     */
    public function isProduction(): bool
    {
        return $this->type === 'production';
    }

    /**
     * Check if this is a test entry.
     */
    public function isTest(): bool
    {
        return $this->type === 'test';
    }

    /**
     * Check if this is a Pest test entry.
     */
    public function isPest(): bool
    {
        return $this->framework === 'pest';
    }

    /**
     * Check if this is a PHPUnit test entry.
     */
    public function isPhpUnit(): bool
    {
        return $this->framework === 'phpunit';
    }

    /**
     * Get the class name from the identifier.
     *
     * For 'App\Services\UserService::create' returns 'App\Services\UserService'
     */
    public function getClassName(): string
    {
        $parts = explode('::', $this->identifier);

        return $parts[0];
    }

    /**
     * Get the method name from the identifier.
     *
     * For 'App\Services\UserService::create' returns 'create'
     * For class-level entries, returns null.
     */
    public function getMethodName(): ?string
    {
        $parts = explode('::', $this->identifier);

        return $parts[1] ?? null;
    }

    /**
     * Get the normalized placeholder ID (without @@ prefix, just @).
     *
     * For '@@A' returns '@A', for '@A' returns '@A'.
     */
    public function getNormalizedPlaceholder(): string
    {
        if ($this->useSeeTag && str_starts_with($this->placeholder, '@@')) {
            return '@'.substr($this->placeholder, 2);
        }

        return $this->placeholder;
    }
}
