<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\SyncAction;

describe('SyncAction', function (): void {
    describe('constructor', function (): void {
        it('creates a basic action', function (): void {
            $action = new SyncAction(
                testFile: '/path/to/tests/UserTest.php',
                testIdentifier: 'Tests\\Unit\\UserTest::test create user',
                testName: 'test create user',
                methodIdentifier: 'App\\Services\\UserService::create',
                methodsToAdd: ['App\\Services\\UserService::create'],
            );

            expect($action->testFile)->toBe('/path/to/tests/UserTest.php');
            expect($action->testIdentifier)->toBe('Tests\\Unit\\UserTest::test create user');
            expect($action->testName)->toBe('test create user');
            expect($action->methodIdentifier)->toBe('App\\Services\\UserService::create');
            expect($action->methodsToAdd)->toBe(['App\\Services\\UserService::create']);
            expect($action->testCase)->toBeNull();
        });

        it('accepts multiple methods to add', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'Tests\\Test::test',
                testName: 'test',
                methodIdentifier: 'App\\User',
                methodsToAdd: ['App\\User::create', 'App\\User::validate', 'App\\User::save'],
            );

            expect($action->methodsToAdd)->toHaveCount(3);
        });
    });

    describe('getFormattedMethod', function (): void {
        it('formats class-level reference', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'Tests\\Test::test',
                testName: 'test',
                methodIdentifier: 'App\\Services\\UserService',
                methodsToAdd: ['App\\Services\\UserService'],
            );

            expect($action->getFormattedMethod())->toBe('App\\Services\\UserService::class');
        });

        it('formats method-level reference', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'Tests\\Test::test',
                testName: 'test',
                methodIdentifier: 'App\\Services\\UserService::create',
                methodsToAdd: ['App\\Services\\UserService::create'],
            );

            expect($action->getFormattedMethod())->toBe("UserService::class.'::create'");
        });

        it('handles deeply nested namespaces', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'Tests\\Test::test',
                testName: 'test',
                methodIdentifier: 'App\\Domain\\User\\Services\\UserService::process',
                methodsToAdd: [],
            );

            expect($action->getFormattedMethod())->toBe("UserService::class.'::process'");
        });
    });

    describe('getPestLinkDisplay', function (): void {
        it('formats Pest linksAndCovers display for method', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'Tests\\Unit\\Services\\UserServiceTest::creates a new user',
                testName: 'creates a new user',
                methodIdentifier: 'App\\Services\\UserService::create',
                methodsToAdd: [],
            );

            expect($action->getPestLinkDisplay())
                ->toBe("->linksAndCovers(UserService::class.'::create')");
        });

        it('formats Pest linksAndCovers display for class', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'Tests\\Test::test',
                testName: 'test',
                methodIdentifier: 'App\\Services\\UserService',
                methodsToAdd: [],
            );

            expect($action->getPestLinkDisplay())
                ->toBe('->linksAndCovers(App\\Services\\UserService::class)');
        });
    });

    describe('getPhpUnitLinkDisplay', function (): void {
        it('formats PHPUnit attribute display for method', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'Tests\\Unit\\Services\\UserServiceTest::creates a new user',
                testName: 'creates a new user',
                methodIdentifier: 'App\\Services\\UserService::create',
                methodsToAdd: [],
            );

            expect($action->getPhpUnitLinkDisplay())
                ->toBe("#[LinksAndCovers(UserService::class, 'create')]");
        });

        it('formats PHPUnit attribute display for class', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'Tests\\Test::test',
                testName: 'test',
                methodIdentifier: 'App\\Services\\UserService',
                methodsToAdd: [],
            );

            expect($action->getPhpUnitLinkDisplay())
                ->toBe('#[LinksAndCovers(UserService::class)]');
        });

        it('handles simple class names', function (): void {
            $action = new SyncAction(
                testFile: '/test.php',
                testIdentifier: 'UserTest::test method',
                testName: 'test method',
                methodIdentifier: 'User::create',
                methodsToAdd: [],
            );

            expect($action->getPhpUnitLinkDisplay())
                ->toBe("#[LinksAndCovers(User::class, 'create')]");
        });
    });
});
