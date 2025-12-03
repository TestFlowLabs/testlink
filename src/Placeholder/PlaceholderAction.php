<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Placeholder;

/**
 * Value object representing a single placeholder pairing action.
 *
 * An action represents one replacement: linking a production method to a test.
 * For N:M placeholders, multiple actions are created (N × M actions).
 */
final readonly class PlaceholderAction
{
    /**
     * @param  string  $placeholderId  The placeholder being resolved (e.g., '@A')
     * @param  PlaceholderEntry  $productionEntry  The production entry to update
     * @param  PlaceholderEntry  $testEntry  The test entry to update
     */
    public function __construct(
        public string $placeholderId,
        public PlaceholderEntry $productionEntry,
        public PlaceholderEntry $testEntry,
    ) {}

    /**
     * Get the production method identifier (e.g., 'App\Services\UserService::create').
     */
    public function getProductionMethodIdentifier(): string
    {
        return $this->productionEntry->identifier;
    }

    /**
     * Get the test identifier (e.g., 'Tests\Unit\UserServiceTest::it creates user').
     */
    public function getTestIdentifier(): string
    {
        return $this->testEntry->identifier;
    }

    /**
     * Get the production class name.
     */
    public function getProductionClassName(): string
    {
        return $this->productionEntry->getClassName();
    }

    /**
     * Get the production method name.
     */
    public function getProductionMethodName(): ?string
    {
        return $this->productionEntry->getMethodName();
    }

    /**
     * Get the test class name.
     */
    public function getTestClassName(): string
    {
        return $this->testEntry->getClassName();
    }

    /**
     * Get the test method name.
     */
    public function getTestMethodName(): ?string
    {
        return $this->testEntry->getMethodName();
    }

    /**
     * Get the production file path.
     */
    public function getProductionFilePath(): string
    {
        return $this->productionEntry->filePath;
    }

    /**
     * Get the test file path.
     */
    public function getTestFilePath(): string
    {
        return $this->testEntry->filePath;
    }

    /**
     * Check if this action is for a Pest test.
     */
    public function isPestTest(): bool
    {
        return $this->testEntry->isPest();
    }

    /**
     * Check if this action is for a PHPUnit test.
     */
    public function isPhpUnitTest(): bool
    {
        return $this->testEntry->isPhpUnit();
    }
}
