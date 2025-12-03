<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Sync\Modifier\OrphanPruner;

describe('OrphanPruner', function (): void {
    beforeEach(function (): void {
        $this->pruner  = new OrphanPruner();
        $this->testDir = sys_get_temp_dir().'/pest-orphan-pruner-tests';
        @mkdir($this->testDir, 0777, true);
    });

    afterEach(function (): void {
        array_map('unlink', glob($this->testDir.'/*.php') ?: []);
        @rmdir($this->testDir);
    });

    describe('findOrphans', function (): void {
        it('finds orphaned linksAndCovers calls', function (): void {
            $filePath = $this->testDir.'/Test.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('test', function () {})
                    ->linksAndCovers('App\User::create');
                PHP);

            $registry = new TestLinkRegistry();
            // Registry is empty, so App\User::create is orphaned

            $orphans = $this->pruner->findOrphans($filePath, $registry);

            expect($orphans)->toHaveCount(1);
            expect($orphans[0]->method)->toBe('App\User::create');
        });

        it('does not report valid links as orphans', function (): void {
            $filePath = $this->testDir.'/Test.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('test', function () {})
                    ->linksAndCovers('App\User::create');
                PHP);

            $registry = new TestLinkRegistry();
            $registry->registerLink('Tests\Unit\Test::test', 'App\User::create');

            $orphans = $this->pruner->findOrphans($filePath, $registry);

            expect($orphans)->toBe([]);
        });

        it('returns empty array for non-existent file', function (): void {
            $registry = new TestLinkRegistry();

            $orphans = $this->pruner->findOrphans('/nonexistent.php', $registry);

            expect($orphans)->toBe([]);
        });

        it('returns empty array for file without linksAndCovers', function (): void {
            $filePath = $this->testDir.'/Test.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('test', function () {
                    expect(true)->toBeTrue();
                });
                PHP);

            $registry = new TestLinkRegistry();

            $orphans = $this->pruner->findOrphans($filePath, $registry);

            expect($orphans)->toBe([]);
        });
    });

    describe('prune', function (): void {
        it('removes orphaned linksAndCovers call', function (): void {
            $code = <<<'PHP'
                <?php

                test('test', function () {})
                    ->linksAndCovers('App\User::create');
                PHP;

            // Create an orphan object manually
            $orphan = new \TestFlowLabs\TestLink\Sync\Modifier\OrphanedCall(
                method: 'App\User::create',
                line: 4,
                endLine: 4,
            );

            $result = $this->pruner->prune($code, [$orphan]);

            expect($result)->not->toContain('linksAndCovers');
            expect($result)->toContain('test(');
        });

        it('returns unchanged code for empty orphans', function (): void {
            $code = <<<'PHP'
                <?php

                test('test', function () {});
                PHP;

            $result = $this->pruner->prune($code, []);

            expect($result)->toBe($code);
        });

        it('handles multiple orphans', function (): void {
            $code = <<<'PHP'
                <?php

                test('test', function () {})
                    ->linksAndCovers('App\User::create')
                    ->linksAndCovers('App\User::update');
                PHP;

            $orphans = [
                new \TestFlowLabs\TestLink\Sync\Modifier\OrphanedCall('App\User::create', 4, 4),
                new \TestFlowLabs\TestLink\Sync\Modifier\OrphanedCall('App\User::update', 5, 5),
            ];

            $result = $this->pruner->prune($code, $orphans);

            expect($result)->not->toContain('linksAndCovers');
        });
    });
});
