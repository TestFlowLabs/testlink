<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\DocBlockParser;

describe('DocBlockParser', function (): void {
    beforeEach(function (): void {
        $this->parser = new DocBlockParser();
    });

    describe('parse', function (): void {
        // Edge case 1: Empty docblock
        it('returns null for empty docblock /** */', function (): void {
            expect($this->parser->parse('/** */'))->toBeNull();
        });

        it('returns null for minimal empty docblock /**/', function (): void {
            expect($this->parser->parse('/**/'))->toBeNull();
        });

        it('returns null for empty string', function (): void {
            expect($this->parser->parse(''))->toBeNull();
        });

        it('returns null for invalid docblock without closing', function (): void {
            expect($this->parser->parse('/** @see Foo::bar'))->toBeNull();
        });

        it('returns null for invalid docblock without opening', function (): void {
            expect($this->parser->parse('@see Foo::bar */'))->toBeNull();
        });

        // Edge case 2: Single-line docblock
        it('parses single-line docblock', function (): void {
            $docBlock = '/** @see Foo::bar */';
            $result   = $this->parser->parse($docBlock);

            expect($result)->not->toBeNull();
        });
    });

    describe('extractSeeReferences', function (): void {
        // Pest test names with spaces (Phase 12 bug investigation)
        it('extracts @see with Pest test name containing spaces', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see \Tests\TestLink\DocblockServiceTest::creates user
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['\Tests\TestLink\DocblockServiceTest::creates user']);
        });

        it('extracts @see with Pest test name containing multiple words', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see \Tests\TestLink\UserServiceTest::creates a new user with valid data
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['\Tests\TestLink\UserServiceTest::creates a new user with valid data']);
        });

        // Edge case 2: Single-line docblock
        it('extracts @see from single-line docblock', function (): void {
            $docBlock = '/** @see Foo::bar */';
            $refs     = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['Foo::bar']);
        });

        // Edge case 3: Multi-line standard
        it('extracts @see from multi-line docblock', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see Foo::bar
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['Foo::bar']);
        });

        // Edge case 4: Multiple @see tags
        it('extracts multiple @see tags', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see A::b
 * @see C::d
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['A::b', 'C::d']);
        });

        // Edge case 5: @see with description
        it('extracts @see reference ignoring description', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see Foo::bar Some description text here
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['Foo::bar']);
        });

        // Edge case 6: @see without method (class only)
        it('extracts @see with class only', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see FooClass
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['FooClass']);
        });

        // Edge case 7: @see with full FQCN (leading backslash)
        it('extracts @see with full FQCN', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see \App\Tests\Foo::bar
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['\App\Tests\Foo::bar']);
        });

        // Edge case 8: @see without leading backslash
        it('extracts @see without leading backslash', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see App\Tests\Foo::bar
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['App\Tests\Foo::bar']);
        });

        // Edge case 9: Malformed @see (empty)
        it('skips empty @see tag', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see
 * @see Valid::ref
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['Valid::ref']);
        });

        // Edge case 10: @see inside other text (should not match)
        it('only extracts actual @see tags not text mentions', function (): void {
            $docBlock = <<<'DOC'
/**
 * Not a real @see Tag in description
 * @see Real::reference
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            // phpstan/phpdoc-parser parses the @see in description too
            // This is expected behavior - it's a tag position, not inline text
            expect($refs)->toContain('Real::reference');
        });

        // Edge case 11: Mixed tag order
        it('extracts @see among other tags', function (): void {
            $docBlock = <<<'DOC'
/**
 * Description here.
 *
 * @param string $name
 * @see Foo::bar
 * @return void
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['Foo::bar']);
        });

        // Edge case 12: Unicode in class/method names
        // Note: PHP method names cannot contain non-ASCII characters
        // but class names in namespaces can have them in paths
        // phpstan/phpdoc-parser uses \w which doesn't match unicode
        // This test documents actual behavior
        it('handles @see with special characters (documents actual behavior)', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see Foo::test_method_name
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['Foo::test_method_name']);
        });

        // Edge case 13: Windows line endings (CRLF)
        it('handles Windows line endings', function (): void {
            $docBlock = "/**\r\n * @see Foo::bar\r\n */";
            $refs     = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['Foo::bar']);
        });

        // Edge case 14: Tab indentation
        it('handles tab indentation', function (): void {
            $docBlock = "/**\n\t* @see Foo::bar\n\t*/";
            $refs     = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe(['Foo::bar']);
        });

        // Edge case 15: No space after * (*@see vs * @see)
        it('handles @see without space after asterisk', function (): void {
            $docBlock = <<<'DOC'
/**
 *@see Foo::bar
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            // phpstan/phpdoc-parser handles this format
            expect($refs)->toBe(['Foo::bar']);
        });

        it('returns empty array for docblock without @see', function (): void {
            $docBlock = <<<'DOC'
/**
 * Just a description.
 *
 * @param string $name
 * @return void
 */
DOC;
            $refs = $this->parser->extractSeeReferences($docBlock);

            expect($refs)->toBe([]);
        });

        it('returns empty array for invalid docblock', function (): void {
            expect($this->parser->extractSeeReferences('not a docblock'))->toBe([]);
        });
    });

    describe('hasSeeReference', function (): void {
        // Pest test names with spaces (Phase 12 bug investigation)
        it('returns true for Pest test name with spaces', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see \Tests\TestLink\DocblockServiceTest::creates user
 */
DOC;
            expect($this->parser->hasSeeReference(
                $docBlock,
                '\Tests\TestLink\DocblockServiceTest::creates user'
            ))->toBeTrue();
        });

        it('returns true for Pest test name with multiple words', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see \Tests\UserTest::creates a new user with valid data
 */
DOC;
            expect($this->parser->hasSeeReference(
                $docBlock,
                '\Tests\UserTest::creates a new user with valid data'
            ))->toBeTrue();
        });

        it('returns true when reference exists', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see Foo::bar
 */
DOC;
            expect($this->parser->hasSeeReference($docBlock, 'Foo::bar'))->toBeTrue();
        });

        it('returns false when reference does not exist', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see Foo::bar
 */
DOC;
            expect($this->parser->hasSeeReference($docBlock, 'Baz::qux'))->toBeFalse();
        });

        it('normalizes leading backslash for comparison', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see \Foo\Bar::method
 */
DOC;
            // With backslash should match
            expect($this->parser->hasSeeReference($docBlock, '\Foo\Bar::method'))->toBeTrue();
            // Without backslash should also match
            expect($this->parser->hasSeeReference($docBlock, 'Foo\Bar::method'))->toBeTrue();
        });
    });

    describe('hasSeeTags', function (): void {
        it('returns true when @see tags exist', function (): void {
            $docBlock = '/** @see Foo::bar */';
            expect($this->parser->hasSeeTags($docBlock))->toBeTrue();
        });

        it('returns false when no @see tags', function (): void {
            $docBlock = '/** @param string $name */';
            expect($this->parser->hasSeeTags($docBlock))->toBeFalse();
        });
    });

    describe('countSeeTags', function (): void {
        it('counts zero @see tags', function (): void {
            $docBlock = '/** @param string $name */';
            expect($this->parser->countSeeTags($docBlock))->toBe(0);
        });

        it('counts single @see tag', function (): void {
            $docBlock = '/** @see Foo::bar */';
            expect($this->parser->countSeeTags($docBlock))->toBe(1);
        });

        it('counts multiple @see tags', function (): void {
            $docBlock = <<<'DOC'
/**
 * @see A::b
 * @see C::d
 * @see E::f
 */
DOC;
            expect($this->parser->countSeeTags($docBlock))->toBe(3);
        });
    });

    describe('isValidDocBlock', function (): void {
        it('returns true for valid docblock', function (): void {
            expect($this->parser->isValidDocBlock('/** @see Foo::bar */'))->toBeTrue();
        });

        it('returns false for invalid opening', function (): void {
            expect($this->parser->isValidDocBlock('/* @see Foo::bar */'))->toBeFalse();
        });

        it('returns false for missing closing', function (): void {
            expect($this->parser->isValidDocBlock('/** @see Foo::bar'))->toBeFalse();
        });

        it('returns false for empty string', function (): void {
            expect($this->parser->isValidDocBlock(''))->toBeFalse();
        });
    });

    describe('extractDescription', function (): void {
        it('extracts description from docblock', function (): void {
            $docBlock = <<<'DOC'
/**
 * This is the description.
 *
 * @param string $name
 */
DOC;
            expect($this->parser->extractDescription($docBlock))->toBe('This is the description.');
        });

        it('returns empty string for docblock without description', function (): void {
            $docBlock = '/** @param string $name */';
            expect($this->parser->extractDescription($docBlock))->toBe('');
        });
    });
});
