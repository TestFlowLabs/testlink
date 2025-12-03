<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Adapter;

use TestFlowLabs\TestLink\Runtime\TestLinkTrait;
use TestFlowLabs\TestLink\Modifier\PestTestModifier;
use TestFlowLabs\TestLink\Sync\Parser\PestTestParser;
use TestFlowLabs\TestLink\Contract\TestParserInterface;
use TestFlowLabs\TestLink\Contract\TestModifierInterface;
use TestFlowLabs\TestLink\Contract\FrameworkAdapterInterface;

/**
 * Adapter for Pest testing framework.
 *
 * Pest tests use method chaining for linking:
 * - ->linksAndCovers(Class::class.'::method') for link + coverage
 * - ->links(Class::class.'::method') for link only
 */
final class PestAdapter implements FrameworkAdapterInterface
{
    private ?TestParserInterface $parser     = null;
    private ?TestModifierInterface $modifier = null;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'pest';
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        return class_exists(\Pest\Plugin::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getParser(): TestParserInterface
    {
        return $this->parser ??= new PestTestParser();
    }

    /**
     * {@inheritDoc}
     */
    public function getModifier(): TestModifierInterface
    {
        return $this->modifier ??= new PestTestModifier();
    }

    /**
     * {@inheritDoc}
     */
    public function registerRuntime(): void
    {
        if (!$this->isAvailable()) {
            return;
        }

        // Register the trait with Pest's plugin system
        \Pest\Plugin::uses(TestLinkTrait::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getTestFilePatterns(): array
    {
        return [
            'tests/**/*.php',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function ownsTestFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            return false;
        }

        // Pest tests use test() or it() functions
        return preg_match('/\b(test|it)\s*\(/', $content) === 1;
    }
}
