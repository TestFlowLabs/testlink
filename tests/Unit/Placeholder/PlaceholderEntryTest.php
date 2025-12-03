<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Placeholder\PlaceholderEntry;

describe('PlaceholderEntry', function (): void {
    describe('constructor', function (): void {
        it('creates entry with all properties')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    placeholder: '@A',
                    identifier: 'App\\Services\\UserService::create',
                    filePath: '/path/to/UserService.php',
                    line: 25,
                    type: 'production',
                    framework: null,
                );

                return [
                    'placeholder' => $entry->placeholder,
                    'identifier'  => $entry->identifier,
                    'filePath'    => $entry->filePath,
                    'line'        => $entry->line,
                    'type'        => $entry->type,
                    'framework'   => $entry->framework,
                ];
            })
            ->toMatchArray([
                'placeholder' => '@A',
                'identifier'  => 'App\\Services\\UserService::create',
                'filePath'    => '/path/to/UserService.php',
                'line'        => 25,
                'type'        => 'production',
                'framework'   => null,
            ]);
    });

    describe('isProduction', function (): void {
        it('returns true for production entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isProduction')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Class::method', '/path', 1, 'production', null
                );

                return $entry->isProduction();
            })
            ->toBeTrue();

        it('returns false for test entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isProduction')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Test::test', '/path', 1, 'test', 'pest'
                );

                return $entry->isProduction();
            })
            ->toBeFalse();
    });

    describe('isTest', function (): void {
        it('returns true for test entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isTest')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Test::test', '/path', 1, 'test', 'pest'
                );

                return $entry->isTest();
            })
            ->toBeTrue();

        it('returns false for production entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isTest')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Class::method', '/path', 1, 'production', null
                );

                return $entry->isTest();
            })
            ->toBeFalse();
    });

    describe('isPest', function (): void {
        it('returns true for Pest test entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isPest')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Test::test', '/path', 1, 'test', 'pest'
                );

                return $entry->isPest();
            })
            ->toBeTrue();

        it('returns false for PHPUnit test entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isPest')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Test::test', '/path', 1, 'test', 'phpunit'
                );

                return $entry->isPest();
            })
            ->toBeFalse();

        it('returns false for production entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isPest')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Class::method', '/path', 1, 'production', null
                );

                return $entry->isPest();
            })
            ->toBeFalse();
    });

    describe('isPhpUnit', function (): void {
        it('returns true for PHPUnit test entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isPhpUnit')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Test::test', '/path', 1, 'test', 'phpunit'
                );

                return $entry->isPhpUnit();
            })
            ->toBeTrue();

        it('returns false for Pest test entry')
            ->linksAndCovers(PlaceholderEntry::class.'::isPhpUnit')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Test::test', '/path', 1, 'test', 'pest'
                );

                return $entry->isPhpUnit();
            })
            ->toBeFalse();
    });

    describe('getClassName', function (): void {
        it('extracts class name from identifier with method')
            ->linksAndCovers(PlaceholderEntry::class.'::getClassName')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::create', '/path', 1, 'production', null
                );

                return $entry->getClassName();
            })
            ->toBe('App\\Services\\UserService');

        it('returns full identifier when no method separator')
            ->linksAndCovers(PlaceholderEntry::class.'::getClassName')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService', '/path', 1, 'production', null
                );

                return $entry->getClassName();
            })
            ->toBe('App\\Services\\UserService');
    });

    describe('getMethodName', function (): void {
        it('extracts method name from identifier')
            ->linksAndCovers(PlaceholderEntry::class.'::getMethodName')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::create', '/path', 1, 'production', null
                );

                return $entry->getMethodName();
            })
            ->toBe('create');

        it('returns null for class-level identifier')
            ->linksAndCovers(PlaceholderEntry::class.'::getMethodName')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService', '/path', 1, 'production', null
                );

                return $entry->getMethodName();
            })
            ->toBeNull();

        it('handles test identifier with description')
            ->linksAndCovers(PlaceholderEntry::class.'::getMethodName')
            ->expect(function () {
                $entry = new PlaceholderEntry(
                    '@A', 'Tests\\Unit\\UserServiceTest::it creates user', '/path', 1, 'test', 'pest'
                );

                return $entry->getMethodName();
            })
            ->toBe('it creates user');
    });
});
