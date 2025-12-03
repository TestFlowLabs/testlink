<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;
use TestFlowLabs\TestLink\Sync\Parser\PestTestParser;

describe('PestTestParser', function (): void {
    beforeEach(function (): void {
        $this->parser = new PestTestParser();
    });

    describe('findTestByName', function (): void {
        it('finds a simple test by name', function (): void {
            $code = <<<'PHP'
                <?php

                test('creates a user', function () {
                    expect(true)->toBeTrue();
                });
                PHP;

            $result = $this->parser->findTestByName($code, 'creates a user');

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('creates a user');
            expect($result->type)->toBe(ParsedTestCase::TYPE_PEST);
        });

        it('finds test using it() syntax', function (): void {
            $code = <<<'PHP'
                <?php

                it('creates a user', function () {
                    expect(true)->toBeTrue();
                });
                PHP;

            $result = $this->parser->findTestByName($code, 'creates a user');

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('creates a user');
        });

        it('returns null for non-existing test', function (): void {
            $code = <<<'PHP'
                <?php

                test('existing test', function () {});
                PHP;

            $result = $this->parser->findTestByName($code, 'non-existing test');

            expect($result)->toBeNull();
        });

        it('finds test inside describe block', function (): void {
            $code = <<<'PHP'
                <?php

                describe('UserService', function () {
                    it('creates a user', function () {
                        expect(true)->toBeTrue();
                    });
                });
                PHP;

            $result = $this->parser->findTestByName($code, 'UserService > creates a user');

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('creates a user');
            expect($result->describePath)->toBe(['UserService']);
        });

        it('finds test in nested describe blocks', function (): void {
            $code = <<<'PHP'
                <?php

                describe('UserService', function () {
                    describe('create method', function () {
                        it('creates a user', function () {
                            expect(true)->toBeTrue();
                        });
                    });
                });
                PHP;

            $result = $this->parser->findTestByName($code, 'UserService > create method > creates a user');

            expect($result)->not->toBeNull();
            expect($result->name)->toBe('creates a user');
            expect($result->describePath)->toBe(['UserService', 'create method']);
        });

        it('returns null for invalid PHP code', function (): void {
            $code = '<?php invalid { code';

            $result = $this->parser->findTestByName($code, 'any test');

            expect($result)->toBeNull();
        });
    });

    describe('findAllTests', function (): void {
        it('finds all top-level tests', function (): void {
            $code = <<<'PHP'
                <?php

                test('first test', function () {});
                test('second test', function () {});
                it('third test', function () {});
                PHP;

            $results = $this->parser->findAllTests($code);

            expect($results)->toHaveCount(3);
            expect($results[0]->name)->toBe('first test');
            expect($results[1]->name)->toBe('second test');
            expect($results[2]->name)->toBe('third test');
        });

        it('finds tests inside describe blocks', function (): void {
            $code = <<<'PHP'
                <?php

                describe('Group', function () {
                    test('nested test', function () {});
                });
                PHP;

            $results = $this->parser->findAllTests($code);

            expect($results)->toHaveCount(1);
            expect($results[0]->getFullName())->toBe('Group > nested test');
        });

        it('returns empty array for invalid code', function (): void {
            $code = '<?php invalid';

            $results = $this->parser->findAllTests($code);

            expect($results)->toBe([]);
        });

        it('returns empty array for code without tests', function (): void {
            $code = <<<'PHP'
                <?php

                function helper() {}
                $variable = 'value';
                PHP;

            $results = $this->parser->findAllTests($code);

            expect($results)->toBe([]);
        });
    });

    describe('existing coversMethod detection', function (): void {
        it('detects existing linksAndCovers calls', function (): void {
            $code = <<<'PHP'
                <?php

                test('creates a user', function () {
                    expect(true)->toBeTrue();
                })->linksAndCovers('App\User::create');
                PHP;

            $result = $this->parser->findTestByName($code, 'creates a user');

            expect($result)->not->toBeNull();
            expect($result->existingCoversMethod)->toContain('App\User::create');
        });

        it('detects multiple linksAndCovers calls', function (): void {
            $code = <<<'PHP'
                <?php

                test('creates a user', function () {
                    expect(true)->toBeTrue();
                })->linksAndCovers('App\User::create')
                  ->linksAndCovers('App\User::validate');
                PHP;

            $result = $this->parser->findTestByName($code, 'creates a user');

            expect($result->existingCoversMethod)->toHaveCount(2);
        });

        it('detects links calls as covers', function (): void {
            $code = <<<'PHP'
                <?php

                test('creates a user', function () {
                    expect(true)->toBeTrue();
                })->links('App\User::create');
                PHP;

            $result = $this->parser->findTestByName($code, 'creates a user');

            expect($result->existingCoversMethod)->toContain('App\User::create');
        });
    });

    describe('line numbers', function (): void {
        it('captures start and end line numbers', function (): void {
            $code = <<<'PHP'
                <?php

                test('multiline test', function () {
                    $a = 1;
                    $b = 2;
                    expect($a + $b)->toBe(3);
                });
                PHP;

            $result = $this->parser->findTestByName($code, 'multiline test');

            expect($result->startLine)->toBeGreaterThan(0);
            expect($result->endLine)->toBeGreaterThanOrEqual($result->startLine);
        });
    });
});
