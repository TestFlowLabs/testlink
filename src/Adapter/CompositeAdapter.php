<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Adapter;

use TestFlowLabs\TestLink\Discovery\FrameworkDetector;
use TestFlowLabs\TestLink\Contract\FrameworkAdapterInterface;

/**
 * Manages multiple framework adapters for hybrid projects.
 *
 * This adapter coordinates between Pest and PHPUnit when both are present,
 * routing test files to the appropriate framework adapter.
 */
final class CompositeAdapter
{
    /** @var list<FrameworkAdapterInterface> */
    private array $adapters = [];

    public function __construct(private readonly FrameworkDetector $detector = new FrameworkDetector())
    {
        $this->initializeAdapters();
    }

    /**
     * Initialize adapters based on detected frameworks.
     */
    private function initializeAdapters(): void
    {
        if ($this->detector->isPestAvailable()) {
            $this->adapters[] = new PestAdapter();
        }

        if ($this->detector->isPhpUnitAvailable()) {
            $this->adapters[] = new PhpUnitAdapter();
        }
    }

    /**
     * Get the appropriate adapter for a test file.
     */
    public function getAdapterForFile(string $filePath): ?FrameworkAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->ownsTestFile($filePath)) {
                return $adapter;
            }
        }

        return null;
    }

    /**
     * Get adapter by framework name.
     */
    public function getAdapterByName(string $name): ?FrameworkAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->getName() === $name) {
                return $adapter;
            }
        }

        return null;
    }

    /**
     * Get all available adapters.
     *
     * @return list<FrameworkAdapterInterface>
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * Get framework description for display.
     *
     * @return list<string>
     */
    public function getAvailableFrameworks(): array
    {
        if ($this->adapters === []) {
            return ['none'];
        }

        // If Pest is available, it includes PHPUnit compatibility
        if ($this->detector->isPestAvailable()) {
            return ['pest (phpunit compatible)'];
        }

        return array_map(
            static fn (FrameworkAdapterInterface $adapter): string => $adapter->getName(),
            $this->adapters
        );
    }

    /**
     * Check if a specific framework is available.
     */
    public function hasFramework(string $name): bool
    {
        return $this->getAdapterByName($name) instanceof FrameworkAdapterInterface;
    }

    /**
     * Get the primary adapter (prefers Pest if available).
     */
    public function getPrimaryAdapter(): ?FrameworkAdapterInterface
    {
        $primary = $this->detector->getPrimaryFramework();

        if ($primary === null) {
            return null;
        }

        return $this->getAdapterByName($primary);
    }

    /**
     * Get all test file patterns from all adapters.
     *
     * @return list<string>
     */
    public function getAllTestFilePatterns(): array
    {
        $patterns = [];

        foreach ($this->adapters as $adapter) {
            $patterns = [...$patterns, ...$adapter->getTestFilePatterns()];
        }

        return array_values(array_unique($patterns));
    }
}
