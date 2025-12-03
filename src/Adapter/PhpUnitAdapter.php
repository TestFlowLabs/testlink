<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Adapter;

use TestFlowLabs\TestLink\Parser\PhpUnitTestParser;
use TestFlowLabs\TestLink\Contract\TestParserInterface;
use TestFlowLabs\TestLink\Modifier\PhpUnitTestModifier;
use TestFlowLabs\TestLink\Contract\TestModifierInterface;
use TestFlowLabs\TestLink\Contract\FrameworkAdapterInterface;

/**
 * Adapter for PHPUnit testing framework.
 *
 * PHPUnit tests use attributes for linking:
 * - #[LinksAndCovers(Class::class, 'method')] for link + coverage
 * - #[Links(Class::class, 'method')] for link only
 */
final class PhpUnitAdapter implements FrameworkAdapterInterface
{
    private ?TestParserInterface $parser     = null;
    private ?TestModifierInterface $modifier = null;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'phpunit';
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        return class_exists(\PHPUnit\Framework\TestCase::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getParser(): TestParserInterface
    {
        return $this->parser ??= new PhpUnitTestParser();
    }

    /**
     * {@inheritDoc}
     */
    public function getModifier(): TestModifierInterface
    {
        return $this->modifier ??= new PhpUnitTestModifier();
    }

    /**
     * {@inheritDoc}
     */
    public function registerRuntime(): void
    {
        // PHPUnit uses attributes, no runtime registration needed.
        // The scanner reads #[Links] and #[LinksAndCovers] via reflection.
    }

    /**
     * {@inheritDoc}
     */
    public function getTestFilePatterns(): array
    {
        return [
            'tests/**/*Test.php',
            'tests/**/*TestCase.php',
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

        // PHPUnit tests extend TestCase
        if (preg_match('/extends\s+TestCase\b/', $content) === 1) {
            return true;
        }

        // Or use PHPUnit TestCase directly
        return preg_match('/use\s+PHPUnit\\\\Framework\\\\TestCase/', $content) === 1;
    }
}
