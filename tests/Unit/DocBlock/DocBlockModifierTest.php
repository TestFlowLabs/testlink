<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\DocBlockModifier;

describe('DocBlockModifier', function (): void {
    beforeEach(function (): void {
        $this->modifier = new DocBlockModifier();
    });

    describe('addSeeTags', function (): void {
        describe('docblock creation', function (): void {
            // Edge case 16: Method with no docblock
            it('creates new docblock for method without one', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('/**');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
                expect($result['code'])->toContain('*/');
            });

            // Edge case 17: Method with single-line /** @test */
            it('adds @see to single-line docblock', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /** @test */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });

            // Edge case 18: Method with attributes above (no docblock)
            it('creates docblock before attributes', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    #[Test]
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                // Docblock should appear before the attribute
                $lines         = explode("\n", $result['code']);
                $docBlockLine  = null;
                $attributeLine = null;
                foreach ($lines as $i => $line) {
                    if (str_contains($line, '/**')) {
                        $docBlockLine = $i;
                    }
                    if (str_contains($line, '#[Test]')) {
                        $attributeLine = $i;
                    }
                }
                expect($docBlockLine)->toBeLessThan($attributeLine);
            });

            // Edge case 19: Method with attributes AND docblock
            it('adds @see to existing docblock with attributes', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * Description.
     */
    #[Test]
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
                expect($result['code'])->toContain('Description.');
            });

            // Edge case 20: Abstract method
            it('creates docblock for abstract method', function (): void {
                $code = <<<'PHP'
<?php

abstract class Foo
{
    abstract public function bar(): void;
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });

            // Edge case 21: Static method
            it('creates docblock for static method', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    public static function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });

            // Edge case 22: Constructor
            it('creates docblock for constructor', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    public function __construct()
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, '__construct', ['\Tests\FooTest::testConstruct']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@see \Tests\FooTest::testConstruct');
            });

            // Edge case 23: Magic method
            it('creates docblock for magic method', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    public function __get(string $name): mixed
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, '__get', ['\Tests\FooTest::testGet']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@see \Tests\FooTest::testGet');
            });

            // Edge case 25: Trait method
            it('creates docblock for trait method', function (): void {
                $code = <<<'PHP'
<?php

trait Foo
{
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });
        });

        describe('indentation', function (): void {
            // Edge case 26: 4-space indent (standard)
            it('preserves 4-space indentation', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['code'])->toContain('    /**');
                expect($result['code'])->toContain('     * @see');
                expect($result['code'])->toContain('     */');
            });

            // Edge case 27: 2-space indent
            it('preserves 2-space indentation', function (): void {
                $code   = "<?php\n\nclass Foo\n{\n  public function bar(): void\n  {\n  }\n}";
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['code'])->toContain('  /**');
                expect($result['code'])->toContain('   * @see');
            });

            // Edge case 28: Tab indent
            it('preserves tab indentation', function (): void {
                $code   = "<?php\n\nclass Foo\n{\n\tpublic function bar(): void\n\t{\n\t}\n}";
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['code'])->toContain("\t/**");
                expect($result['code'])->toContain("\t * @see");
            });

            // Edge case 31: Deep nesting (8+ spaces)
            it('preserves deep nesting indentation', function (): void {
                $code   = "<?php\n\nclass Foo\n{\n        public function bar(): void\n        {\n        }\n}";
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['code'])->toContain('        /**');
                expect($result['code'])->toContain('         * @see');
            });
        });

        describe('existing docblock modification', function (): void {
            // Edge case 32: Docblock with only description
            it('adds @see after description', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * Method description.
     */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('Method description.');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });

            // Edge case 33: Docblock with @param tags
            it('adds @see after @param tags', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @param string $name
     */
    public function bar(string $name): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@param string $name');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });

            // Edge case 34: Docblock with @return tag
            it('adds @see after @return tag', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @return string
     */
    public function bar(): string
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@return string');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });

            // Edge case 35: Docblock with @throws tags
            it('adds @see after @throws tag', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @throws \Exception
     */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@throws \Exception');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });

            // Edge case 36: Docblock with @deprecated
            it('adds @see to docblock with @deprecated', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @deprecated
     */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@deprecated');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });

            // Edge case 37: Docblock with @inheritdoc
            it('adds @see to docblock with @inheritdoc', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * {@inheritdoc}
     */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('{@inheritdoc}');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar');
            });
        });

        describe('@see tag position', function (): void {
            // Edge case 45: Add @see when @see already exists (duplicate)
            it('skips duplicate @see references', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @see \Tests\FooTest::testBar
     */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeFalse();
            });

            // Edge case 46: Add multiple @see at once
            it('adds multiple @see tags', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', [
                    '\Tests\FooTest::testBar1',
                    '\Tests\FooTest::testBar2',
                    '\Tests\FooTest::testBar3',
                ]);

                expect($result['changed'])->toBeTrue();
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar1');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar2');
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar3');
            });

            it('adds only non-duplicate @see when some already exist', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @see \Tests\FooTest::testBar1
     */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', [
                    '\Tests\FooTest::testBar1', // Duplicate
                    '\Tests\FooTest::testBar2', // New
                ]);

                expect($result['changed'])->toBeTrue();
                // Count occurrences - testBar1 should appear only once
                expect(substr_count($result['code'], 'testBar1'))->toBe(1);
                expect($result['code'])->toContain('@see \Tests\FooTest::testBar2');
            });
        });

        describe('content preservation', function (): void {
            // Edge case 50: Preserve existing description exactly
            it('preserves multi-line description', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * This is a multi-line
     * description that spans
     * several lines.
     */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['code'])->toContain('This is a multi-line');
                expect($result['code'])->toContain('description that spans');
                expect($result['code'])->toContain('several lines.');
            });

            // Edge case 51: Preserve @param with complex types
            it('preserves complex @param types', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @param array<string, array{name: string, value: int}> $data
     */
    public function bar(array $data): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['code'])->toContain('@param array<string, array{name: string, value: int}> $data');
            });

            // Edge case 53: Preserve @return with nullable types
            it('preserves nullable @return types', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @return ?string
     */
    public function bar(): ?string
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['code'])->toContain('@return ?string');
            });

            // Edge case 54: Preserve custom tags
            it('preserves custom tags like @api', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @api
     * @since 1.0.0
     */
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

                expect($result['code'])->toContain('@api');
                expect($result['code'])->toContain('@since 1.0.0');
            });
        });

        describe('error handling', function (): void {
            it('returns unchanged code when method not found', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'nonexistent', ['\Tests\FooTest::testBar']);

                expect($result['changed'])->toBeFalse();
                expect($result['code'])->toBe($code);
            });

            it('returns unchanged code when references array is empty', function (): void {
                $code = <<<'PHP'
<?php

class Foo
{
    public function bar(): void
    {
    }
}
PHP;
                $result = $this->modifier->addSeeTags($code, 'bar', []);

                expect($result['changed'])->toBeFalse();
            });
        });
    });

    describe('removeSeeTags', function (): void {
        // Edge case 47: Remove single @see
        it('removes single @see tag', function (): void {
            $code = <<<'PHP'
<?php

class Foo
{
    /**
     * Description.
     *
     * @see \Tests\FooTest::testBar
     */
    public function bar(): void
    {
    }
}
PHP;
            $result = $this->modifier->removeSeeTags($code, 'bar', ['\Tests\FooTest::testBar']);

            expect($result['changed'])->toBeTrue();
            expect($result['code'])->not->toContain('@see');
            expect($result['code'])->toContain('Description.');
        });

        // Edge case 48: Remove one of multiple @see
        it('removes one of multiple @see tags', function (): void {
            $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @see \Tests\FooTest::testBar1
     * @see \Tests\FooTest::testBar2
     */
    public function bar(): void
    {
    }
}
PHP;
            $result = $this->modifier->removeSeeTags($code, 'bar', ['\Tests\FooTest::testBar1']);

            expect($result['changed'])->toBeTrue();
            expect($result['code'])->not->toContain('testBar1');
            expect($result['code'])->toContain('@see \Tests\FooTest::testBar2');
        });

        it('returns unchanged when reference not found', function (): void {
            $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @see \Tests\FooTest::testBar
     */
    public function bar(): void
    {
    }
}
PHP;
            $result = $this->modifier->removeSeeTags($code, 'bar', ['\Tests\FooTest::testOther']);

            expect($result['changed'])->toBeFalse();
        });

        it('normalizes backslash for removal', function (): void {
            $code = <<<'PHP'
<?php

class Foo
{
    /**
     * @see \Tests\FooTest::testBar
     */
    public function bar(): void
    {
    }
}
PHP;
            // Remove without leading backslash
            $result = $this->modifier->removeSeeTags($code, 'bar', ['Tests\FooTest::testBar']);

            expect($result['changed'])->toBeTrue();
            expect($result['code'])->not->toContain('@see');
        });
    });

    describe('removeAllSeeTags', function (): void {
        // Edge case 49: Remove all @see
        it('removes all @see tags', function (): void {
            $code = <<<'PHP'
<?php

class Foo
{
    /**
     * Description.
     *
     * @see \Tests\FooTest::testBar1
     * @see \Tests\FooTest::testBar2
     * @see \Tests\FooTest::testBar3
     */
    public function bar(): void
    {
    }
}
PHP;
            $result = $this->modifier->removeAllSeeTags($code, 'bar');

            expect($result['changed'])->toBeTrue();
            expect($result['code'])->not->toContain('@see');
            expect($result['code'])->toContain('Description.');
        });

        it('returns unchanged when no @see tags exist', function (): void {
            $code = <<<'PHP'
<?php

class Foo
{
    /**
     * Description.
     */
    public function bar(): void
    {
    }
}
PHP;
            $result = $this->modifier->removeAllSeeTags($code, 'bar');

            expect($result['changed'])->toBeFalse();
        });
    });
});
