<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\FqcnIssue;

describe('FqcnIssue', function (): void {
    describe('constructor and properties', function (): void {
        it('stores all provided values', function (): void {
            $issue = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                methodName: 'create',
                className: 'App\Services\UserService',
                isResolvable: true,
                errorMessage: null,
            );

            expect($issue->originalReference)->toBe('UserTest::testCreate');
            expect($issue->resolvedFqcn)->toBe('\Tests\Unit\UserTest::testCreate');
            expect($issue->filePath)->toBe('/app/src/Services/UserService.php');
            expect($issue->line)->toBe(42);
            expect($issue->context)->toBe('production');
            expect($issue->methodName)->toBe('create');
            expect($issue->className)->toBe('App\Services\UserService');
            expect($issue->isResolvable)->toBeTrue();
            expect($issue->errorMessage)->toBeNull();
        });

        it('stores unresolvable issue with error message', function (): void {
            $issue = new FqcnIssue(
                originalReference: 'UnknownClass::method',
                resolvedFqcn: null,
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                isResolvable: false,
                errorMessage: "Could not resolve 'UnknownClass'",
            );

            expect($issue->resolvedFqcn)->toBeNull();
            expect($issue->isResolvable)->toBeFalse();
            expect($issue->errorMessage)->toBe("Could not resolve 'UnknownClass'");
        });
    });

    describe('isFqcn', function (): void {
        it('returns true for reference starting with backslash', function (): void {
            expect(FqcnIssue::isFqcn('\Tests\UserTest::testCreate'))->toBeTrue();
        });

        it('returns true for class-only FQCN', function (): void {
            expect(FqcnIssue::isFqcn('\Tests\UserTest'))->toBeTrue();
        });

        it('returns false for reference without backslash', function (): void {
            expect(FqcnIssue::isFqcn('UserTest::testCreate'))->toBeFalse();
        });

        it('returns false for short class name', function (): void {
            expect(FqcnIssue::isFqcn('UserTest'))->toBeFalse();
        });

        it('returns false for empty string', function (): void {
            expect(FqcnIssue::isFqcn(''))->toBeFalse();
        });
    });

    describe('getMethodIdentifier', function (): void {
        it('returns ClassName::methodName when both are set', function (): void {
            $issue = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                methodName: 'create',
                className: 'App\Services\UserService',
            );

            expect($issue->getMethodIdentifier())->toBe('App\Services\UserService::create');
        });

        it('returns null when className is null', function (): void {
            $issue = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                methodName: 'create',
            );

            expect($issue->getMethodIdentifier())->toBeNull();
        });

        it('returns null when methodName is null', function (): void {
            $issue = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                className: 'App\Services\UserService',
            );

            expect($issue->getMethodIdentifier())->toBeNull();
        });
    });

    describe('isFixable', function (): void {
        it('returns true when resolvable and has resolved FQCN', function (): void {
            $issue = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                isResolvable: true,
            );

            expect($issue->isFixable())->toBeTrue();
        });

        it('returns false when not resolvable', function (): void {
            $issue = new FqcnIssue(
                originalReference: 'UnknownClass::method',
                resolvedFqcn: null,
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                isResolvable: false,
                errorMessage: "Could not resolve 'UnknownClass'",
            );

            expect($issue->isFixable())->toBeFalse();
        });

        it('returns false when resolved FQCN is null', function (): void {
            $issue = new FqcnIssue(
                originalReference: 'testMethod',
                resolvedFqcn: null,
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                isResolvable: true,
            );

            expect($issue->isFixable())->toBeFalse();
        });
    });
});
