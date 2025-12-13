<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\ReverseTestedByAction;

describe('ReverseTestedByAction', function (): void {
    describe('constructor', function (): void {
        it('creates a basic action', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/path/to/src/Services/UserService.php',
                methodIdentifier: 'App\\Services\\UserService::create',
                testIdentifier: 'Tests\\Unit\\UserServiceTest::test_creates_user',
                className: 'App\\Services\\UserService',
                methodName: 'create',
            );

            expect($action->productionFile)->toBe('/path/to/src/Services/UserService.php');
            expect($action->methodIdentifier)->toBe('App\\Services\\UserService::create');
            expect($action->testIdentifier)->toBe('Tests\\Unit\\UserServiceTest::test_creates_user');
            expect($action->className)->toBe('App\\Services\\UserService');
            expect($action->methodName)->toBe('create');
        });
    });

    describe('getTestedByDisplay', function (): void {
        it('formats #[TestedBy] display with method', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/path/to/src/Services/UserService.php',
                methodIdentifier: 'App\\Services\\UserService::create',
                testIdentifier: 'Tests\\Unit\\UserServiceTest::test_creates_user',
                className: 'App\\Services\\UserService',
                methodName: 'create',
            );

            expect($action->getTestedByDisplay())
                ->toBe("#[TestedBy(UserServiceTest::class, 'test_creates_user')]");
        });

        it('formats #[TestedBy] display without method', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/path/to/src/Services/UserService.php',
                methodIdentifier: 'App\\Services\\UserService::create',
                testIdentifier: 'Tests\\Unit\\UserServiceTest',
                className: 'App\\Services\\UserService',
                methodName: 'create',
            );

            expect($action->getTestedByDisplay())
                ->toBe('#[TestedBy(UserServiceTest::class)]');
        });

        it('handles deeply nested test class namespaces', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\Services\\User\\UserServiceTest::test_create',
                className: 'App\\User',
                methodName: 'create',
            );

            expect($action->getTestedByDisplay())
                ->toBe("#[TestedBy(UserServiceTest::class, 'test_create')]");
        });
    });

    describe('getTestClassName', function (): void {
        it('extracts class name from identifier with method', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserServiceTest::test_creates_user',
                className: 'App\\User',
                methodName: 'create',
            );

            expect($action->getTestClassName())
                ->toBe('Tests\\Unit\\UserServiceTest');
        });

        it('returns identifier when no method', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserServiceTest',
                className: 'App\\User',
                methodName: 'create',
            );

            expect($action->getTestClassName())
                ->toBe('Tests\\Unit\\UserServiceTest');
        });
    });

    describe('getTestMethodName', function (): void {
        it('extracts method name from identifier', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserServiceTest::test_creates_user',
                className: 'App\\User',
                methodName: 'create',
            );

            expect($action->getTestMethodName())
                ->toBe('test_creates_user');
        });

        it('returns null when no method in identifier', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserServiceTest',
                className: 'App\\User',
                methodName: 'create',
            );

            expect($action->getTestMethodName())
                ->toBeNull();
        });

        it('handles Pest test names with spaces', function (): void {
            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserServiceTest::it creates a new user',
                className: 'App\\User',
                methodName: 'create',
            );

            expect($action->getTestMethodName())
                ->toBe('it creates a new user');
        });
    });
});
