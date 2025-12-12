<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\FqcnIssue;
use TestFlowLabs\TestLink\DocBlock\FqcnIssueRegistry;

describe('FqcnIssueRegistry', function (): void {
    describe('register and getIssuesForFile', function (): void {
        it('registers and retrieves issues for a file', function (): void {
            $registry = new FqcnIssueRegistry();

            $issue = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/UserService.php',
                line: 42,
                context: 'production',
            );

            $registry->register($issue);

            $issues = $registry->getIssuesForFile('/app/src/UserService.php');

            expect($issues)->toHaveCount(1);
            expect($issues[0])->toBe($issue);
        });

        it('returns empty array for unknown file', function (): void {
            $registry = new FqcnIssueRegistry();

            $issues = $registry->getIssuesForFile('/unknown/file.php');

            expect($issues)->toBe([]);
        });

        it('registers multiple issues for same file', function (): void {
            $registry = new FqcnIssueRegistry();

            $issue1 = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/UserService.php',
                line: 42,
                context: 'production',
            );

            $issue2 = new FqcnIssue(
                originalReference: 'UserTest::testUpdate',
                resolvedFqcn: '\Tests\Unit\UserTest::testUpdate',
                filePath: '/app/src/UserService.php',
                line: 56,
                context: 'production',
            );

            $registry->register($issue1);
            $registry->register($issue2);

            $issues = $registry->getIssuesForFile('/app/src/UserService.php');

            expect($issues)->toHaveCount(2);
        });
    });

    describe('getAllByFile', function (): void {
        it('returns all issues grouped by file', function (): void {
            $registry = new FqcnIssueRegistry();

            $issue1 = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/UserService.php',
                line: 42,
                context: 'production',
            );

            $issue2 = new FqcnIssue(
                originalReference: 'OrderTest::testPlace',
                resolvedFqcn: '\Tests\Unit\OrderTest::testPlace',
                filePath: '/app/src/OrderService.php',
                line: 30,
                context: 'production',
            );

            $registry->register($issue1);
            $registry->register($issue2);

            $allByFile = $registry->getAllByFile();

            expect($allByFile)->toHaveCount(2);
            expect($allByFile)->toHaveKey('/app/src/UserService.php');
            expect($allByFile)->toHaveKey('/app/src/OrderService.php');
        });

        it('returns empty array when no issues', function (): void {
            $registry = new FqcnIssueRegistry();

            expect($registry->getAllByFile())->toBe([]);
        });
    });

    describe('getFixableIssues', function (): void {
        it('returns only fixable issues', function (): void {
            $registry = new FqcnIssueRegistry();

            $fixable = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/UserService.php',
                line: 42,
                context: 'production',
                isResolvable: true,
            );

            $unfixable = new FqcnIssue(
                originalReference: 'UnknownClass::method',
                resolvedFqcn: null,
                filePath: '/app/src/UserService.php',
                line: 56,
                context: 'production',
                isResolvable: false,
                errorMessage: 'Not found',
            );

            $registry->register($fixable);
            $registry->register($unfixable);

            $fixableIssues = $registry->getFixableIssues();

            expect($fixableIssues)->toHaveCount(1);
            expect($fixableIssues[0])->toBe($fixable);
        });
    });

    describe('getUnfixableIssues', function (): void {
        it('returns only unfixable issues', function (): void {
            $registry = new FqcnIssueRegistry();

            $fixable = new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: '/app/src/UserService.php',
                line: 42,
                context: 'production',
                isResolvable: true,
            );

            $unfixable = new FqcnIssue(
                originalReference: 'UnknownClass::method',
                resolvedFqcn: null,
                filePath: '/app/src/UserService.php',
                line: 56,
                context: 'production',
                isResolvable: false,
                errorMessage: 'Not found',
            );

            $registry->register($fixable);
            $registry->register($unfixable);

            $unfixableIssues = $registry->getUnfixableIssues();

            expect($unfixableIssues)->toHaveCount(1);
            expect($unfixableIssues[0])->toBe($unfixable);
        });
    });

    describe('count', function (): void {
        it('returns total count of issues', function (): void {
            $registry = new FqcnIssueRegistry();

            expect($registry->count())->toBe(0);

            $registry->register(new FqcnIssue(
                originalReference: 'Test1',
                resolvedFqcn: '\Test1',
                filePath: '/file1.php',
                line: 1,
                context: 'production',
            ));

            expect($registry->count())->toBe(1);

            $registry->register(new FqcnIssue(
                originalReference: 'Test2',
                resolvedFqcn: '\Test2',
                filePath: '/file2.php',
                line: 2,
                context: 'test',
            ));

            expect($registry->count())->toBe(2);
        });
    });

    describe('countFixable', function (): void {
        it('returns count of fixable issues only', function (): void {
            $registry = new FqcnIssueRegistry();

            $registry->register(new FqcnIssue(
                originalReference: 'Test1',
                resolvedFqcn: '\Test1',
                filePath: '/file1.php',
                line: 1,
                context: 'production',
                isResolvable: true,
            ));

            $registry->register(new FqcnIssue(
                originalReference: 'Test2',
                resolvedFqcn: null,
                filePath: '/file2.php',
                line: 2,
                context: 'production',
                isResolvable: false,
            ));

            expect($registry->countFixable())->toBe(1);
        });
    });

    describe('hasIssues', function (): void {
        it('returns false when empty', function (): void {
            $registry = new FqcnIssueRegistry();

            expect($registry->hasIssues())->toBeFalse();
        });

        it('returns true when has issues', function (): void {
            $registry = new FqcnIssueRegistry();

            $registry->register(new FqcnIssue(
                originalReference: 'Test1',
                resolvedFqcn: '\Test1',
                filePath: '/file1.php',
                line: 1,
                context: 'production',
            ));

            expect($registry->hasIssues())->toBeTrue();
        });
    });

    describe('clear', function (): void {
        it('removes all issues', function (): void {
            $registry = new FqcnIssueRegistry();

            $registry->register(new FqcnIssue(
                originalReference: 'Test1',
                resolvedFqcn: '\Test1',
                filePath: '/file1.php',
                line: 1,
                context: 'production',
            ));

            expect($registry->hasIssues())->toBeTrue();

            $registry->clear();

            expect($registry->hasIssues())->toBeFalse();
            expect($registry->count())->toBe(0);
        });
    });
});
