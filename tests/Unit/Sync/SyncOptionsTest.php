<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\SyncOptions;

describe('SyncOptions', function (): void {
    describe('constructor defaults', function (): void {
        it('has all flags disabled by default', function (): void {
            $options = new SyncOptions();

            expect($options->dryRun)->toBeFalse();
            expect($options->linkOnly)->toBeFalse();
            expect($options->prune)->toBeFalse();
            expect($options->force)->toBeFalse();
            expect($options->path)->toBeNull();
        });

        it('accepts individual flags', function (): void {
            $options = new SyncOptions(dryRun: true, linkOnly: true);

            expect($options->dryRun)->toBeTrue();
            expect($options->linkOnly)->toBeTrue();
        });

        it('accepts path option', function (): void {
            $options = new SyncOptions(path: '/custom/path');

            expect($options->path)->toBe('/custom/path');
        });
    });

    describe('validation', function (): void {
        it('throws when prune is used without force', function (): void {
            expect(fn () => new SyncOptions(prune: true))
                ->toThrow(InvalidArgumentException::class);
        });

        it('allows prune with force', function (): void {
            $options = new SyncOptions(prune: true, force: true);

            expect($options->prune)->toBeTrue();
            expect($options->force)->toBeTrue();
        });
    });

    describe('fromArguments', function (): void {
        it('parses dry-run flag', function (): void {
            $options = SyncOptions::fromArguments(['--dry-run']);

            expect($options->dryRun)->toBeTrue();
            expect($options->linkOnly)->toBeFalse();
        });

        it('parses link-only flag', function (): void {
            $options = SyncOptions::fromArguments(['--link-only']);

            expect($options->linkOnly)->toBeTrue();
        });

        it('parses prune and force flags together', function (): void {
            $options = SyncOptions::fromArguments(['--prune', '--force']);

            expect($options->prune)->toBeTrue();
            expect($options->force)->toBeTrue();
        });

        it('parses path with value', function (): void {
            $options = SyncOptions::fromArguments(['--path=/src/Services']);

            expect($options->path)->toBe('/src/Services');
        });

        it('parses multiple flags', function (): void {
            $options = SyncOptions::fromArguments([
                '--dry-run',
                '--link-only',
                '--path=/app',
            ]);

            expect($options->dryRun)->toBeTrue();
            expect($options->linkOnly)->toBeTrue();
            expect($options->path)->toBe('/app');
        });

        it('ignores unknown arguments', function (): void {
            $options = SyncOptions::fromArguments([
                '--unknown',
                '--dry-run',
                'positional',
            ]);

            expect($options->dryRun)->toBeTrue();
            expect($options->linkOnly)->toBeFalse();
        });

        it('returns empty options for empty arguments', function (): void {
            $options = SyncOptions::fromArguments([]);

            expect($options->dryRun)->toBeFalse();
            expect($options->path)->toBeNull();
        });
    });
});
