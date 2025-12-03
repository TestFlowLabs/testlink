<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Parser\PhpUnitTestParser;
use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;

beforeEach(function (): void {
    $this->parser       = new PhpUnitTestParser();
    $this->fixturesPath = __DIR__.'/../../Fixtures/PhpUnit';
});

test('it parses simple phpunit file')
    ->linksAndCovers(PhpUnitTestParser::class.'::parseFile')
    ->expect(function () {
        $tests = $this->parser->parseFile($this->fixturesPath.'/SimpleTestCase.php');

        return count($tests);
    })
    ->toBe(3);

test('it finds test by name')
    ->linksAndCovers(PhpUnitTestParser::class.'::findTestByName')
    ->expect(function () {
        $code = file_get_contents($this->fixturesPath.'/SimpleTestCase.php');
        $test = $this->parser->findTestByName($code, 'test_it_does_something');

        return [
            'instance' => $test instanceof ParsedTestCase,
            'name'     => $test?->name,
        ];
    })
    ->toMatchArray([
        'instance' => true,
        'name'     => 'test_it_does_something',
    ]);

test('it detects phpunit test files')
    ->linksAndCovers(PhpUnitTestParser::class.'::supports')
    ->expect(fn () => $this->parser->supports($this->fixturesPath.'/SimpleTestCase.php'))
    ->toBeTrue();

test('it parses tests with link attributes')
    ->linksAndCovers(PhpUnitTestParser::class.'::parseFile')
    ->expect(function () {
        $tests         = $this->parser->parseFile($this->fixturesPath.'/AttributeTestCase.php');
        $testWithLinks = array_filter($tests, fn ($t) => $t->existingCoversMethod !== []);

        return count($testWithLinks);
    })
    ->toBeGreaterThan(0);

test('it returns type as phpunit')
    ->linksAndCovers(PhpUnitTestParser::class.'::parseFile')
    ->expect(function () {
        $tests = $this->parser->parseFile($this->fixturesPath.'/SimpleTestCase.php');

        return $tests[0]->type;
    })
    ->toBe(ParsedTestCase::TYPE_PHPUNIT);

test('it returns correct number of tests')
    ->linksAndCovers(PhpUnitTestParser::class.'::parseFile')
    ->expect(function () {
        $tests = $this->parser->parseFile($this->fixturesPath.'/SimpleTestCase.php');

        return count($tests);
    })
    ->toBe(3);
