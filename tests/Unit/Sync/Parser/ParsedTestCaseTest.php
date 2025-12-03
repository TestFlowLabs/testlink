<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;

describe('ParsedTestCase', function (): void {
    describe('constructor', function (): void {
        it('creates a basic test case', function (): void {
            $testCase = new ParsedTestCase(
                name: 'it creates a user',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 10,
                endLine: 15,
            );

            expect($testCase->name)->toBe('it creates a user');
            expect($testCase->type)->toBe('pest');
            expect($testCase->startLine)->toBe(10);
            expect($testCase->endLine)->toBe(15);
            expect($testCase->existingCoversMethod)->toBe([]);
            expect($testCase->describePath)->toBe([]);
        });

        it('creates a test case with existing covers', function (): void {
            $testCase = new ParsedTestCase(
                name: 'test',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 1,
                endLine: 5,
                existingCoversMethod: ['App\\User::create', 'App\\User::validate'],
            );

            expect($testCase->existingCoversMethod)->toBe(['App\\User::create', 'App\\User::validate']);
        });

        it('creates a test case with describe path', function (): void {
            $testCase = new ParsedTestCase(
                name: 'creates user',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 20,
                endLine: 25,
                describePath: ['UserService', 'create method'],
            );

            expect($testCase->describePath)->toBe(['UserService', 'create method']);
        });
    });

    describe('getFullName', function (): void {
        it('returns name when no describe path', function (): void {
            $testCase = new ParsedTestCase(
                name: 'it creates a user',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 1,
                endLine: 5,
            );

            expect($testCase->getFullName())->toBe('it creates a user');
        });

        it('includes describe path in full name', function (): void {
            $testCase = new ParsedTestCase(
                name: 'creates user',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 1,
                endLine: 5,
                describePath: ['UserService', 'create method'],
            );

            expect($testCase->getFullName())->toBe('UserService > create method > creates user');
        });

        it('handles single describe level', function (): void {
            $testCase = new ParsedTestCase(
                name: 'test',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 1,
                endLine: 5,
                describePath: ['Unit'],
            );

            expect($testCase->getFullName())->toBe('Unit > test');
        });
    });

    describe('hasCoversMethod', function (): void {
        it('returns true for existing method', function (): void {
            $testCase = new ParsedTestCase(
                name: 'test',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 1,
                endLine: 5,
                existingCoversMethod: ['App\\User::create'],
            );

            expect($testCase->hasCoversMethod('App\\User::create'))->toBeTrue();
        });

        it('returns false for non-existing method', function (): void {
            $testCase = new ParsedTestCase(
                name: 'test',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 1,
                endLine: 5,
                existingCoversMethod: ['App\\User::create'],
            );

            expect($testCase->hasCoversMethod('App\\User::delete'))->toBeFalse();
        });

        it('returns false when no covers exist', function (): void {
            $testCase = new ParsedTestCase(
                name: 'test',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 1,
                endLine: 5,
            );

            expect($testCase->hasCoversMethod('Any::method'))->toBeFalse();
        });
    });

    describe('type checks', function (): void {
        it('identifies Pest tests', function (): void {
            $testCase = new ParsedTestCase(
                name: 'test',
                type: ParsedTestCase::TYPE_PEST,
                startLine: 1,
                endLine: 5,
            );

            expect($testCase->isPest())->toBeTrue();
            expect($testCase->isPhpUnit())->toBeFalse();
        });

        it('identifies PHPUnit tests', function (): void {
            $testCase = new ParsedTestCase(
                name: 'testMethod',
                type: ParsedTestCase::TYPE_PHPUNIT,
                startLine: 1,
                endLine: 5,
            );

            expect($testCase->isPest())->toBeFalse();
            expect($testCase->isPhpUnit())->toBeTrue();
        });
    });

    describe('constants', function (): void {
        it('defines correct type constants', function (): void {
            expect(ParsedTestCase::TYPE_PEST)->toBe('pest');
            expect(ParsedTestCase::TYPE_PHPUNIT)->toBe('phpunit');
        });
    });
});
