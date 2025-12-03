<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\SyncAction;
use TestFlowLabs\TestLink\Sync\SyncResult;

describe('SyncResult', function (): void {
    describe('constructor defaults', function (): void {
        it('has sensible defaults', function (): void {
            $result = new SyncResult();

            expect($result->isDryRun)->toBeFalse();
            expect($result->linksAdded)->toBe(0);
            expect($result->linksPruned)->toBe(0);
            expect($result->filesModified)->toBe(0);
            expect($result->modifiedFiles)->toBe([]);
            expect($result->prunedFiles)->toBe([]);
            expect($result->errors)->toBe([]);
            expect($result->actions)->toBe([]);
        });
    });

    describe('dryRun factory', function (): void {
        it('creates dry-run result with actions', function (): void {
            $action = new SyncAction(
                testFile: '/path/to/test.php',
                testIdentifier: 'Tests\\Unit\\UserTest::test create',
                testName: 'test create',
                methodIdentifier: 'App\\User::create',
                methodsToAdd: ['App\\User::create'],
            );

            $result = SyncResult::dryRun([$action]);

            expect($result->isDryRun)->toBeTrue();
            expect($result->linksAdded)->toBe(1);
            expect($result->actions)->toHaveCount(1);
        });

        it('counts multiple methods across actions', function (): void {
            $action1 = new SyncAction(
                testFile: '/test1.php',
                testIdentifier: 'Tests\\Test1::test',
                testName: 'test',
                methodIdentifier: 'App\\A::method1',
                methodsToAdd: ['App\\A::method1', 'App\\A::method2'],
            );

            $action2 = new SyncAction(
                testFile: '/test2.php',
                testIdentifier: 'Tests\\Test2::test',
                testName: 'test',
                methodIdentifier: 'App\\B::method',
                methodsToAdd: ['App\\B::method'],
            );

            $result = SyncResult::dryRun([$action1, $action2]);

            expect($result->linksAdded)->toBe(3);
        });

        it('handles empty actions', function (): void {
            $result = SyncResult::dryRun([]);

            expect($result->isDryRun)->toBeTrue();
            expect($result->linksAdded)->toBe(0);
            expect($result->actions)->toBe([]);
        });
    });

    describe('applied factory', function (): void {
        it('creates result for applied changes', function (): void {
            $result = SyncResult::applied([
                '/path/to/Test.php' => ['App\\User::create', 'App\\User::update'],
            ]);

            expect($result->isDryRun)->toBeFalse();
            expect($result->linksAdded)->toBe(2);
            expect($result->filesModified)->toBe(1);
            expect($result->modifiedFiles)->toBe([
                '/path/to/Test.php' => ['App\\User::create', 'App\\User::update'],
            ]);
        });

        it('counts files modified correctly', function (): void {
            $result = SyncResult::applied([
                '/test1.php' => ['Method1'],
                '/test2.php' => ['Method2', 'Method3'],
            ]);

            expect($result->filesModified)->toBe(2);
            expect($result->linksAdded)->toBe(3);
        });

        it('includes pruned files', function (): void {
            $result = SyncResult::applied(
                ['/test.php' => ['NewMethod']],
                ['/test.php' => ['OldMethod']],
            );

            expect($result->linksAdded)->toBe(1);
            expect($result->linksPruned)->toBe(1);
            expect($result->filesModified)->toBe(1); // Same file counts once
        });

        it('handles empty changes', function (): void {
            $result = SyncResult::applied([]);

            expect($result->linksAdded)->toBe(0);
            expect($result->filesModified)->toBe(0);
        });
    });

    describe('withErrors factory', function (): void {
        it('creates result with errors', function (): void {
            $result = SyncResult::withErrors(['Error 1', 'Error 2']);

            expect($result->errors)->toBe(['Error 1', 'Error 2']);
            expect($result->linksAdded)->toBe(0);
        });
    });

    describe('hasErrors', function (): void {
        it('returns true when errors exist', function (): void {
            $result = SyncResult::withErrors(['An error']);

            expect($result->hasErrors())->toBeTrue();
        });

        it('returns false when no errors', function (): void {
            $result = new SyncResult();

            expect($result->hasErrors())->toBeFalse();
        });
    });

    describe('hasChanges', function (): void {
        it('returns true when links added', function (): void {
            $result = SyncResult::applied(['/test.php' => ['Method']]);

            expect($result->hasChanges())->toBeTrue();
        });

        it('returns true when links pruned', function (): void {
            $result = SyncResult::applied([], ['/test.php' => ['Method']]);

            expect($result->hasChanges())->toBeTrue();
        });

        it('returns false when no changes', function (): void {
            $result = new SyncResult();

            expect($result->hasChanges())->toBeFalse();
        });
    });

    describe('merge', function (): void {
        it('merges two results', function (): void {
            $result1 = SyncResult::applied(['/test1.php' => ['Method1']]);
            $result2 = SyncResult::applied(['/test2.php' => ['Method2']]);

            $merged = $result1->merge($result2);

            expect($merged->linksAdded)->toBe(2);
            expect($merged->filesModified)->toBe(2);
            expect($merged->modifiedFiles)->toHaveKey('/test1.php');
            expect($merged->modifiedFiles)->toHaveKey('/test2.php');
        });

        it('merges errors', function (): void {
            $result1 = SyncResult::withErrors(['Error 1']);
            $result2 = SyncResult::withErrors(['Error 2']);

            $merged = $result1->merge($result2);

            expect($merged->errors)->toBe(['Error 1', 'Error 2']);
        });

        it('merges same file methods', function (): void {
            $result1 = SyncResult::applied(['/test.php' => ['Method1']]);
            $result2 = SyncResult::applied(['/test.php' => ['Method2']]);

            $merged = $result1->merge($result2);

            expect($merged->modifiedFiles['/test.php'])->toBe(['Method1', 'Method2']);
        });

        it('preserves dry-run status from first result', function (): void {
            $result1 = SyncResult::dryRun([]);
            $result2 = SyncResult::applied(['/test.php' => ['Method']]);

            $merged = $result1->merge($result2);

            expect($merged->isDryRun)->toBeTrue();
        });
    });
});
