<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;
use TestFlowLabs\TestLink\Modifier\PhpUnitTestModifier;

beforeEach(function (): void {
    $this->modifier = new PhpUnitTestModifier();
});

test('it injects LinksAndCovers attribute')
    ->linksAndCovers(PhpUnitTestModifier::class.'::injectLinks')
    ->expect(function () {
        $code = <<<'PHP'
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testItCreatesUser(): void
    {
        $this->assertTrue(true);
    }
}
PHP;
        $testCase = new ParsedTestCase(
            name: 'testItCreatesUser',
            type: ParsedTestCase::TYPE_PHPUNIT,
            startLine: 9,
            endLine: 12,
        );

        return $this->modifier->injectLinks(
            $code,
            $testCase,
            ['App\\Services\\UserService::create'],
            withCoverage: true,
        );
    })
    ->toContain('#[LinksAndCovers(');

test('it injects Links attribute when withCoverage is false')
    ->linksAndCovers(PhpUnitTestModifier::class.'::injectLinks')
    ->expect(function () {
        $code = <<<'PHP'
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testItCreatesUser(): void
    {
        $this->assertTrue(true);
    }
}
PHP;
        $testCase = new ParsedTestCase(
            name: 'testItCreatesUser',
            type: ParsedTestCase::TYPE_PHPUNIT,
            startLine: 9,
            endLine: 12,
        );

        return $this->modifier->injectLinks(
            $code,
            $testCase,
            ['App\\Services\\UserService::create'],
            withCoverage: false,
        );
    })
    ->toContain('#[Links(');

test('it supports phpunit test cases')
    ->linksAndCovers(PhpUnitTestModifier::class.'::supports')
    ->expect(function () {
        $phpunitTest = new ParsedTestCase(
            name: 'testSomething',
            type: ParsedTestCase::TYPE_PHPUNIT,
            startLine: 1,
            endLine: 1,
        );

        return $this->modifier->supports($phpunitTest);
    })
    ->toBeTrue();

test('it does not support pest test cases')
    ->linksAndCovers(PhpUnitTestModifier::class.'::supports')
    ->expect(function () {
        $pestTest = new ParsedTestCase(
            name: 'test',
            type: ParsedTestCase::TYPE_PEST,
            startLine: 1,
            endLine: 1,
        );

        return $this->modifier->supports($pestTest);
    })
    ->toBeFalse();

test('it adds use statement if not present')
    ->linksAndCovers(PhpUnitTestModifier::class.'::injectLinks')
    ->expect(function () {
        $code = <<<'PHP'
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testItCreatesUser(): void
    {
        $this->assertTrue(true);
    }
}
PHP;
        $testCase = new ParsedTestCase(
            name: 'testItCreatesUser',
            type: ParsedTestCase::TYPE_PHPUNIT,
            startLine: 9,
            endLine: 12,
        );

        return $this->modifier->injectLinks(
            $code,
            $testCase,
            ['App\\Services\\UserService::create'],
            withCoverage: true,
        );
    })
    ->toContain('use TestFlowLabs\\TestingAttributes\\LinksAndCovers;');
