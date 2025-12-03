<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Modifier\PestTestModifier;
use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;

beforeEach(function (): void {
    $this->modifier = new PestTestModifier();
});

test('it injects linksAndCovers method')
    ->linksAndCovers(PestTestModifier::class.'::injectLinks')
    ->expect(function () {
        $code = <<<'PHP'
<?php
test('it creates user')
    ->expect(true)
    ->toBeTrue();
PHP;
        $testCase = new ParsedTestCase(
            name: 'it creates user',
            type: ParsedTestCase::TYPE_PEST,
            startLine: 2,
            endLine: 4,
        );

        return $this->modifier->injectLinks(
            $code,
            $testCase,
            ['App\\Services\\UserService::create'],
            withCoverage: true,
        );
    })
    ->toContain('->linksAndCovers(');

test('it injects links method when withCoverage is false')
    ->linksAndCovers(PestTestModifier::class.'::injectLinks')
    ->expect(function () {
        $code = <<<'PHP'
<?php
test('it creates user')
    ->expect(true)
    ->toBeTrue();
PHP;
        $testCase = new ParsedTestCase(
            name: 'it creates user',
            type: ParsedTestCase::TYPE_PEST,
            startLine: 2,
            endLine: 4,
        );

        return $this->modifier->injectLinks(
            $code,
            $testCase,
            ['App\\Services\\UserService::create'],
            withCoverage: false,
        );
    })
    ->toContain('->links(');

test('it does not inject duplicate links')
    ->linksAndCovers(PestTestModifier::class.'::injectLinks')
    ->expect(function () {
        $code = <<<'PHP'
<?php
test('it creates user')
    ->linksAndCovers(App\Services\UserService::class.'::create')
    ->expect(true)
    ->toBeTrue();
PHP;
        $testCase = new ParsedTestCase(
            name: 'it creates user',
            type: ParsedTestCase::TYPE_PEST,
            startLine: 2,
            endLine: 5,
            existingCoversMethod: ['App\\Services\\UserService::create'],
        );

        $original = $code;

        return $this->modifier->injectLinks(
            $code,
            $testCase,
            ['App\\Services\\UserService::create'],
            withCoverage: true,
        ) === $original;
    })
    ->toBeTrue();

test('it supports pest test cases')
    ->linksAndCovers(PestTestModifier::class.'::supports')
    ->expect(function () {
        $pestTest = new ParsedTestCase(
            name: 'test',
            type: ParsedTestCase::TYPE_PEST,
            startLine: 1,
            endLine: 1,
        );

        return $this->modifier->supports($pestTest);
    })
    ->toBeTrue();

test('it does not support phpunit test cases')
    ->linksAndCovers(PestTestModifier::class.'::supports')
    ->expect(function () {
        $phpunitTest = new ParsedTestCase(
            name: 'testSomething',
            type: ParsedTestCase::TYPE_PHPUNIT,
            startLine: 1,
            endLine: 1,
        );

        return $this->modifier->supports($phpunitTest);
    })
    ->toBeFalse();

test('it removes link methods')
    ->linksAndCovers(PestTestModifier::class.'::removeLinks')
    ->expect(function () {
        $code = <<<'PHP'
<?php
test('it creates user')
    ->linksAndCovers(App\Services\UserService::class.'::create')
    ->expect(true)
    ->toBeTrue();
PHP;
        $testCase = new ParsedTestCase(
            name: 'it creates user',
            type: ParsedTestCase::TYPE_PEST,
            startLine: 2,
            endLine: 5,
        );

        return $this->modifier->removeLinks(
            $code,
            $testCase,
            ['App\\Services\\UserService::create'],
        );
    })
    ->not->toContain('->linksAndCovers(');
