<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\SyncAction;
use TestFlowLabs\TestLink\Sync\SyncResult;
use TestFlowLabs\TestLink\Sync\SyncReporter;

describe('SyncReporter', function (): void {
    beforeEach(function (): void {
        // Use a memory stream for capturing output
        $this->stream   = fopen('php://memory', 'w+');
        $this->reporter = new SyncReporter($this->stream);
    });

    afterEach(function (): void {
        fclose($this->stream);
    });

    describe('reportDryRun', function (): void {
        it('outputs action details', function (): void {
            $action = new SyncAction(
                testFile: '/path/to/Test.php',
                testIdentifier: 'Tests\\UserTest::test create',
                testName: 'test create',
                methodIdentifier: 'App\\User::create',
                methodsToAdd: ['App\\User::create'],
            );

            $this->reporter->reportDryRun([$action]);

            rewind($this->stream);
            $output = stream_get_contents($this->stream);

            expect($output)->toContain('App\\User::create');
            expect($output)->toContain('Test.php');
        });

        it('outputs no links message when empty', function (): void {
            $this->reporter->reportDryRun([]);

            rewind($this->stream);
            $output = stream_get_contents($this->stream);

            expect($output)->toContain('No links found to sync');
        });
    });

    describe('reportResults', function (): void {
        it('outputs applied changes', function (): void {
            $result = SyncResult::applied([
                '/path/to/Test.php' => ['App\\User::create'],
            ]);

            $this->reporter->reportResults($result);

            rewind($this->stream);
            $output = stream_get_contents($this->stream);

            expect($output)->toContain('Test.php');
            expect($output)->toContain('Sync complete');
        });

        it('outputs pruned files', function (): void {
            $result = SyncResult::applied(
                [],
                ['/path/to/Test.php' => ['App\\User::orphaned']],
            );

            $this->reporter->reportResults($result);

            rewind($this->stream);
            $output = stream_get_contents($this->stream);

            expect($output)->toContain('pruned');
        });

        it('outputs error messages when errors exist', function (): void {
            $result = SyncResult::withErrors(['Error 1', 'Error 2']);

            $this->reporter->reportResults($result);

            rewind($this->stream);
            $output = stream_get_contents($this->stream);

            expect($output)->toContain('Error 1');
            expect($output)->toContain('Error 2');
        });

        it('outputs sync message when no changes', function (): void {
            $result = new SyncResult();

            $this->reporter->reportResults($result);

            rewind($this->stream);
            $output = stream_get_contents($this->stream);

            expect($output)->toContain('already in sync');
        });
    });

    describe('reportNoChanges', function (): void {
        it('outputs no changes message', function (): void {
            $this->reporter->reportNoChanges();

            rewind($this->stream);
            $output = stream_get_contents($this->stream);

            expect($output)->toContain('already in sync');
        });
    });

    describe('reportPruneRequiresForce', function (): void {
        it('outputs force requirement message', function (): void {
            $this->reporter->reportPruneRequiresForce();

            rewind($this->stream);
            $output = stream_get_contents($this->stream);

            expect($output)->toContain('--force');
            expect($output)->toContain('--prune');
        });
    });
});
