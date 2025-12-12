<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

/**
 * Value object representing a @see tag entry found in a docblock.
 *
 * Stores metadata about @see tags for duplicate detection,
 * orphan validation, and modification tracking.
 */
final readonly class SeeTagEntry
{
    /**
     * @param  string  $reference  The @see target (e.g., "\Tests\UserServiceTest::testCreate")
     * @param  string  $filePath  Absolute path to file containing the @see tag
     * @param  int  $line  Line number where the @see tag appears
     * @param  string  $context  Either "production" or "test"
     * @param  string|null  $methodName  Name of method containing the @see (null for class-level)
     * @param  string|null  $className  Fully qualified class name containing the method
     */
    public function __construct(
        public string $reference,
        public string $filePath,
        public int $line,
        public string $context,
        public ?string $methodName = null,
        public ?string $className = null,
    ) {}

    /**
     * Get the method identifier (ClassName::methodName) if both are set.
     */
    public function getMethodIdentifier(): ?string
    {
        if ($this->className === null || $this->methodName === null) {
            return null;
        }

        return $this->className.'::'.$this->methodName;
    }

    /**
     * Check if this @see points to a valid target.
     *
     * @param  array<string>  $validTargets  List of valid target identifiers
     */
    public function hasValidTarget(array $validTargets): bool
    {
        $normalizedRef = $this->getNormalizedReference();

        foreach ($validTargets as $target) {
            // Normalize target for comparison (strip leading backslash)
            $normalizedTarget = ltrim($target, '\\');

            if ($normalizedRef === $normalizedTarget) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is a production-side @see tag.
     */
    public function isProduction(): bool
    {
        return $this->context === 'production';
    }

    /**
     * Check if this is a test-side @see tag.
     */
    public function isTest(): bool
    {
        return $this->context === 'test';
    }

    /**
     * Normalize the reference for comparison (strip leading backslash).
     */
    public function getNormalizedReference(): string
    {
        return ltrim($this->reference, '\\');
    }
}
