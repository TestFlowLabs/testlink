<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\SyncAction;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Sync\Modifier\TestFileModifier;

describe('TestFileModifier', function (): void {
    beforeEach(function (): void {
        $this->modifier = new TestFileModifier();
        $this->testDir  = sys_get_temp_dir().'/pest-file-modifier-tests';
        @mkdir($this->testDir, 0777, true);
    });

    afterEach(function (): void {
        array_map('unlink', glob($this->testDir.'/*.php') ?: []);
        @rmdir($this->testDir);
    });

    describe('apply', function (): void {
        it('applies sync actions to files', function (): void {
            $filePath = $this->testDir.'/UserTest.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('creates a user', function () {
                    expect(true)->toBeTrue();
                });
                PHP);

            $action = new SyncAction(
                testFile: $filePath,
                testIdentifier: 'Tests\\Unit\\UserTest::creates a user',
                testName: 'creates a user',
                methodIdentifier: 'App\\User::create',
                methodsToAdd: ['App\\User::create'],
            );

            $result = $this->modifier->apply([$action]);

            expect($result->hasErrors())->toBeFalse();
            expect($result->linksAdded)->toBe(1);

            $content = file_get_contents($filePath);
            expect($content)->toContain('linksAndCovers');
        });

        it('reports error for non-existent file', function (): void {
            $action = new SyncAction(
                testFile: '/nonexistent/file.php',
                testIdentifier: 'Tests\\Test::test',
                testName: 'test',
                methodIdentifier: 'App\\Method',
                methodsToAdd: ['App\\Method'],
            );

            $result = $this->modifier->apply([$action]);

            expect($result->hasErrors())->toBeTrue();
            expect($result->errors[0])->toContain('not found');
        });

        it('reports error when test case not found', function (): void {
            $filePath = $this->testDir.'/Test.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('existing test', function () {});
                PHP);

            $action = new SyncAction(
                testFile: $filePath,
                testIdentifier: 'Tests\\Test::nonexistent test',
                testName: 'nonexistent test',
                methodIdentifier: 'App\\Method',
                methodsToAdd: ['App\\Method'],
            );

            $result = $this->modifier->apply([$action]);

            expect($result->hasErrors())->toBeTrue();
        });

        it('uses links() when linkOnly is specified', function (): void {
            $filePath = $this->testDir.'/Test.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('test', function () {});
                PHP);

            $action = new SyncAction(
                testFile: $filePath,
                testIdentifier: 'Tests\\Test::test',
                testName: 'test',
                methodIdentifier: 'App\\Method',
                methodsToAdd: ['App\\Method'],
            );

            $result = $this->modifier->apply([$action], linkOnly: true);

            expect($result->hasErrors())->toBeFalse();

            $content = file_get_contents($filePath);
            expect($content)->toContain('links');
        });

        it('groups actions by file', function (): void {
            $filePath = $this->testDir.'/Test.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('first', function () {});
                test('second', function () {});
                PHP);

            $actions = [
                new SyncAction(
                    testFile: $filePath,
                    testIdentifier: 'Tests\\Test::first',
                    testName: 'first',
                    methodIdentifier: 'App\\A',
                    methodsToAdd: ['App\\A'],
                ),
                new SyncAction(
                    testFile: $filePath,
                    testIdentifier: 'Tests\\Test::second',
                    testName: 'second',
                    methodIdentifier: 'App\\B',
                    methodsToAdd: ['App\\B'],
                ),
            ];

            $result = $this->modifier->apply($actions);

            expect($result->hasErrors())->toBeFalse();
            expect($result->filesModified)->toBe(1);
            expect($result->linksAdded)->toBe(2);
        });
    });

    describe('prune', function (): void {
        it('prunes orphaned linksAndCovers calls', function (): void {
            $filePath = $this->testDir.'/Test.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('test', function () {})
                    ->linksAndCovers('App\User::orphaned');
                PHP);

            $registry = new TestLinkRegistry();
            // Registry empty = orphaned

            $result = $this->modifier->prune([$filePath], $registry);

            expect($result->linksPruned)->toBe(1);

            $content = file_get_contents($filePath);
            expect($content)->not->toContain('linksAndCovers');
        });

        it('skips non-existent files', function (): void {
            $registry = new TestLinkRegistry();

            $result = $this->modifier->prune(['/nonexistent.php'], $registry);

            expect($result->linksPruned)->toBe(0);
        });

        it('does not prune valid links', function (): void {
            $filePath = $this->testDir.'/Test.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('test', function () {})
                    ->linksAndCovers('App\User::valid');
                PHP);

            $registry = new TestLinkRegistry();
            $registry->registerLink('Tests\Unit\Test::test', 'App\User::valid');

            $result = $this->modifier->prune([$filePath], $registry);

            expect($result->linksPruned)->toBe(0);

            $content = file_get_contents($filePath);
            expect($content)->toContain('linksAndCovers');
        });
    });
});
