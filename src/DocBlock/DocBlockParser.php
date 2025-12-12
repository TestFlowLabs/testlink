<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

use Throwable;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;

/**
 * Parses PHP docblocks using phpstan/phpdoc-parser.
 *
 * Provides a simplified interface for extracting @see tags
 * and other metadata from docblock comments.
 */
final class DocBlockParser
{
    private readonly Lexer $lexer;
    private readonly PhpDocParser $parser;

    public function __construct()
    {
        $config          = new ParserConfig(usedAttributes: ['lines' => true]);
        $this->lexer     = new Lexer($config);
        $constExprParser = new ConstExprParser($config);
        $typeParser      = new TypeParser($config, $constExprParser);
        $this->parser    = new PhpDocParser($config, $typeParser, $constExprParser);
    }

    /**
     * Parse a docblock string into an AST.
     *
     * Returns null if the docblock is invalid or cannot be parsed.
     */
    public function parse(string $docBlock): ?PhpDocNode
    {
        $docBlock = trim($docBlock);

        // Handle empty or invalid docblocks
        if (in_array($docBlock, ['', '/**/', '/** */'], true)) {
            return null;
        }

        // Ensure docblock has proper format
        if (!str_starts_with($docBlock, '/**') || !str_ends_with($docBlock, '*/')) {
            return null;
        }

        try {
            $tokens        = $this->lexer->tokenize($docBlock);
            $tokenIterator = new TokenIterator($tokens);

            return $this->parser->parse($tokenIterator);
        } catch (Throwable) {
            // Return null for malformed docblocks
            return null;
        }
    }

    /**
     * Extract all @see references from a docblock.
     *
     * @return list<string> List of @see targets (e.g., ["\\Tests\\FooTest::bar", "\\Tests\\BazTest::qux"])
     */
    public function extractSeeReferences(string $docBlock): array
    {
        $node = $this->parse($docBlock);

        if (!$node instanceof \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode) {
            return [];
        }

        $references = [];

        foreach ($node->getTagsByName('@see') as $tag) {
            if ($tag->value instanceof GenericTagValueNode) {
                $value = trim($tag->value->value);

                // Skip empty @see tags
                if ($value === '') {
                    continue;
                }

                // Extract just the reference (before any description)
                // @see \Foo\Bar::method Some description -> \Foo\Bar::method
                $reference = $this->extractReferenceFromValue($value);

                if ($reference !== '') {
                    $references[] = $reference;
                }
            }
        }

        return $references;
    }

    /**
     * Check if docblock has a @see tag for a specific reference.
     *
     * Comparison is normalized (ignores leading backslash).
     */
    public function hasSeeReference(string $docBlock, string $reference): bool
    {
        $normalizedRef = ltrim($reference, '\\');
        $references    = $this->extractSeeReferences($docBlock);

        foreach ($references as $existingRef) {
            if (ltrim($existingRef, '\\') === $normalizedRef) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a docblock has any @see tags.
     */
    public function hasSeeTags(string $docBlock): bool
    {
        return $this->extractSeeReferences($docBlock) !== [];
    }

    /**
     * Count the number of @see tags in a docblock.
     */
    public function countSeeTags(string $docBlock): int
    {
        return count($this->extractSeeReferences($docBlock));
    }

    /**
     * Extract reference from @see value (may include description).
     *
     * Supports both PHPUnit and Pest test naming conventions:
     * - PHPUnit: camelCase or snake_case methods (testFoo, test_bar)
     * - Pest: lowercase names with spaces (handles orphan test)
     *
     * Examples:
     *   "\Foo\Bar::method" -> "\Foo\Bar::method"
     *   "\Foo\Bar::testMethod Some description" -> "\Foo\Bar::testMethod"
     *   "\Foo\Bar::handles orphan test" -> "\Foo\Bar::handles orphan test"
     *   "Foo\Bar" -> "Foo\Bar"
     */
    private function extractReferenceFromValue(string $value): string
    {
        // Match class reference with optional method
        // Pattern handles both PHPUnit and Pest naming conventions:
        // - test\w+ : PHPUnit methods starting with 'test' (no spaces)
        // - [a-z]+(?:\s+[a-z]+)* : Pest lowercase names with spaces (stops at capital letters)
        // - \w+ : Other methods (fallback, no spaces)
        if (preg_match('/^(\\\\?[\w\\\\]+(?:::(?:test\w+|[a-z]+(?:\s+[a-z]+)*|\w+))?)\s*/', $value, $matches)) {
            return $matches[1];
        }

        // If no match, return trimmed value (might be unusual format)
        return $value;
    }

    /**
     * Check if a string looks like a valid docblock.
     */
    public function isValidDocBlock(string $text): bool
    {
        $text = trim($text);

        return str_starts_with($text, '/**')
            && str_ends_with($text, '*/')
            && $this->parse($text) instanceof \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
    }

    /**
     * Extract the description (text before any tags) from a docblock.
     */
    public function extractDescription(string $docBlock): string
    {
        $node = $this->parse($docBlock);

        if (!$node instanceof \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode) {
            return '';
        }

        $description = [];

        foreach ($node->children as $child) {
            // Text nodes come before tag nodes
            if ($child instanceof \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode) {
                $text = trim((string) $child);
                if ($text !== '') {
                    $description[] = $text;
                }
            }
        }

        return implode("\n", $description);
    }
}
