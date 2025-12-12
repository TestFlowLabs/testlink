<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\SeeTagEntry;

describe('SeeTagEntry', function (): void {
    describe('constructor and properties', function (): void {
        it('stores all provided values', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                methodName: 'create',
                className: 'App\Services\UserService',
            );

            expect($entry->reference)->toBe('\Tests\UserServiceTest::testCreate');
            expect($entry->filePath)->toBe('/app/src/Services/UserService.php');
            expect($entry->line)->toBe(42);
            expect($entry->context)->toBe('production');
            expect($entry->methodName)->toBe('create');
            expect($entry->className)->toBe('App\Services\UserService');
        });

        it('allows null for methodName and className', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest',
                filePath: '/app/src/Services/UserService.php',
                line: 10,
                context: 'production',
            );

            expect($entry->methodName)->toBeNull();
            expect($entry->className)->toBeNull();
        });
    });

    describe('getMethodIdentifier', function (): void {
        it('returns ClassName::methodName when both are set', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                methodName: 'create',
                className: 'App\Services\UserService',
            );

            expect($entry->getMethodIdentifier())->toBe('App\Services\UserService::create');
        });

        it('returns null when className is null', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                methodName: 'create',
                className: null,
            );

            expect($entry->getMethodIdentifier())->toBeNull();
        });

        it('returns null when methodName is null', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                methodName: null,
                className: 'App\Services\UserService',
            );

            expect($entry->getMethodIdentifier())->toBeNull();
        });

        it('returns null when both are null', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            expect($entry->getMethodIdentifier())->toBeNull();
        });
    });

    describe('hasValidTarget', function (): void {
        it('returns true when reference is in valid targets', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            $validTargets = [
                '\Tests\UserServiceTest::testCreate',
                '\Tests\OrderServiceTest::testPlace',
            ];

            expect($entry->hasValidTarget($validTargets))->toBeTrue();
        });

        it('returns false when reference is not in valid targets', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\DeletedTest::testRemoved',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            $validTargets = [
                '\Tests\UserServiceTest::testCreate',
                '\Tests\OrderServiceTest::testPlace',
            ];

            expect($entry->hasValidTarget($validTargets))->toBeFalse();
        });

        it('returns false when valid targets is empty', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            expect($entry->hasValidTarget([]))->toBeFalse();
        });

        it('performs exact match (case sensitive)', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            $validTargets = ['\Tests\userservicetest::testcreate'];

            expect($entry->hasValidTarget($validTargets))->toBeFalse();
        });
    });

    describe('isProduction', function (): void {
        it('returns true when context is production', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            expect($entry->isProduction())->toBeTrue();
        });

        it('returns false when context is test', function (): void {
            $entry = new SeeTagEntry(
                reference: '\App\Services\UserService::create',
                filePath: '/app/tests/Unit/UserServiceTest.php',
                line: 42,
                context: 'test',
            );

            expect($entry->isProduction())->toBeFalse();
        });
    });

    describe('isTest', function (): void {
        it('returns true when context is test', function (): void {
            $entry = new SeeTagEntry(
                reference: '\App\Services\UserService::create',
                filePath: '/app/tests/Unit/UserServiceTest.php',
                line: 42,
                context: 'test',
            );

            expect($entry->isTest())->toBeTrue();
        });

        it('returns false when context is production', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            expect($entry->isTest())->toBeFalse();
        });
    });

    describe('getNormalizedReference', function (): void {
        it('strips leading backslash', function (): void {
            $entry = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            expect($entry->getNormalizedReference())->toBe('Tests\UserServiceTest::testCreate');
        });

        it('returns unchanged if no leading backslash', function (): void {
            $entry = new SeeTagEntry(
                reference: 'Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            expect($entry->getNormalizedReference())->toBe('Tests\UserServiceTest::testCreate');
        });

        it('strips all leading backslashes', function (): void {
            $entry = new SeeTagEntry(
                reference: '\\\\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
            );

            // ltrim removes ALL leading backslashes
            expect($entry->getNormalizedReference())->toBe('Tests\UserServiceTest::testCreate');
        });
    });
});
