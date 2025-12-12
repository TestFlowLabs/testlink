<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

/**
 * Value object representing a non-FQCN @see tag issue.
 *
 * Stores metadata about @see tags that don't use fully qualified
 * class names, enabling validation reporting and auto-fixing.
 */
final readonly class FqcnIssue
{
    /**
     * @param  string  $originalReference  The original @see reference (e.g., "UserTest::testCreate")
     * @param  string|null  $resolvedFqcn  The resolved FQCN if resolvable (e.g., "\Tests\Unit\UserTest::testCreate")
     * @param  string  $filePath  Absolute path to the file
     * @param  int  $line  Line number where the @see tag appears
     * @param  string  $context  Either "production" or "test"
     * @param  string|null  $methodName  Name of method containing the @see
     * @param  string|null  $className  FQCN of class containing the method
     * @param  bool  $isResolvable  Whether the short name can be resolved
     * @param  string|null  $errorMessage  Error message if not resolvable
     */
    public function __construct(
        public string $originalReference,
        public ?string $resolvedFqcn,
        public string $filePath,
        public int $line,
        public string $context,
        public ?string $methodName = null,
        public ?string $className = null,
        public bool $isResolvable = true,
        public ?string $errorMessage = null,
    ) {}

    /**
     * Check if the reference is already FQCN format.
     */
    public static function isFqcn(string $reference): bool
    {
        return str_starts_with($reference, '\\');
    }

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
     * Check if this issue can be auto-fixed.
     */
    public function isFixable(): bool
    {
        return $this->isResolvable && $this->resolvedFqcn !== null;
    }
}
