<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;
use TestFlowLabs\TestLink\Sync\Parser\PestTestParser;

beforeEach(function (): void {
    $this->parser       = new PestTestParser();
    $this->fixturesPath = __DIR__.'/../../Fixtures/Pest';
});

test('it parses simple pest file')
    ->linksAndCovers(PestTestParser::class.'::parseFile')
    ->expect(function () {
        $tests = $this->parser->parseFile($this->fixturesPath.'/SimpleTest.php');

        return count($tests);
    })
    ->toBe(3);

test('it finds test by name')
    ->linksAndCovers(PestTestParser::class.'::findTestByName')
    ->expect(function () {
        $code = file_get_contents($this->fixturesPath.'/SimpleTest.php');
        $test = $this->parser->findTestByName($code, 'it does something');

        return [
            'instance' => $test instanceof ParsedTestCase,
            'name'     => $test?->name,
        ];
    })
    ->toMatchArray([
        'instance' => true,
        'name'     => 'it does something',
    ]);

test('it detects pest test files')
    ->linksAndCovers(PestTestParser::class.'::supports')
    ->expect(fn () => $this->parser->supports($this->fixturesPath.'/SimpleTest.php'))
    ->toBeTrue();

test('it parses describe blocks')
    ->linksAndCovers(PestTestParser::class.'::parseFile')
    ->expect(function () {
        $tests     = $this->parser->parseFile($this->fixturesPath.'/DescribeBlockTest.php');
        $testNames = array_map(fn ($t) => $t->getFullName(), $tests);

        return $testNames;
    })
    ->toContain('UserService > it creates a user')
    ->toContain('UserService > validation > it validates email');

test('it extracts existing link methods')
    ->linksAndCovers(PestTestParser::class.'::parseFile')
    ->expect(function () {
        $tests         = $this->parser->parseFile($this->fixturesPath.'/ChainedMethodsTest.php');
        $testWithLinks = array_filter($tests, fn ($t) => $t->existingCoversMethod !== []);

        return count($testWithLinks);
    })
    ->toBeGreaterThan(0);

test('it returns type as pest')
    ->linksAndCovers(PestTestParser::class.'::parseFile')
    ->expect(function () {
        $tests = $this->parser->parseFile($this->fixturesPath.'/SimpleTest.php');

        return $tests[0]->type;
    })
    ->toBe(ParsedTestCase::TYPE_PEST);
