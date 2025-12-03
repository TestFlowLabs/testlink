<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Contract;

/**
 * Interface for framework-specific adapters.
 *
 * Each adapter handles the specifics of a testing framework (Pest, PHPUnit, etc.)
 * including parsing, modifying, and runtime registration.
 */
interface FrameworkAdapterInterface
{
    /**
     * Get the framework name (e.g., 'pest', 'phpunit').
     */
    public function getName(): string;

    /**
     * Check if this framework is available in the project.
     */
    public function isAvailable(): bool;

    /**
     * Get the test parser for this framework.
     */
    public function getParser(): TestParserInterface;

    /**
     * Get the test modifier for this framework.
     */
    public function getModifier(): TestModifierInterface;

    /**
     * Register runtime hooks for this framework.
     *
     * This is called during bootstrap to enable link tracking at runtime.
     */
    public function registerRuntime(): void;

    /**
     * Get test file patterns for this framework.
     *
     * @return list<string> Glob patterns like ['tests/**\/*Test.php']
     */
    public function getTestFilePatterns(): array;

    /**
     * Check if a test file belongs to this framework.
     */
    public function ownsTestFile(string $filePath): bool;
}
