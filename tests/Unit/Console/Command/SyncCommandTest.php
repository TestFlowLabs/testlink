<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Sync\SyncResult;
use TestFlowLabs\TestLink\Sync\SyncOptions;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\Console\Command\SyncCommand;

describe('SyncCommand', function (): void {
    beforeEach(function (): void {
        $this->command = new SyncCommand();
        $this->parser  = new ArgumentParser();
        $this->output  = new Output();
    });

    describe('prune validation', function (): void {
        it('returns exit code 1 when --prune used without --force')
            ->linksAndCovers(SyncCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'sync', '--prune']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBe(1);

        it('accepts --prune with --force flags')
            ->linksAndCovers(SyncCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'sync', '--prune', '--force', '--dry-run']);

                return [
                    'has_prune' => $this->parser->hasOption('prune'),
                    'has_force' => $this->parser->hasOption('force'),
                ];
            })
            ->toMatchArray([
                'has_prune' => true,
                'has_force' => true,
            ]);
    });

    describe('command execution', function (): void {
        it('returns int exit code with --dry-run')
            ->linksAndCovers(SyncCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'sync', '--dry-run']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBeInt();

        it('accepts --link-only flag')
            ->linksAndCovers(SyncCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'sync', '--link-only', '--dry-run']);

                return $this->parser->hasOption('link-only');
            })
            ->toBeTrue();

        it('accepts --path option')
            ->linksAndCovers(SyncCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'sync', '--path=/src/Services', '--dry-run']);

                return $this->parser->getString('path');
            })
            ->toBe('/src/Services');

        it('accepts --framework option')
            ->linksAndCovers(SyncCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'sync', '--framework=pest', '--dry-run']);

                return $this->parser->getString('framework');
            })
            ->toBe('pest');
    });

    describe('SyncOptions integration', function (): void {
        it('creates options with default values')
            ->linksAndCovers(SyncOptions::class.'::__construct')
            ->expect(function () {
                $options = new SyncOptions();

                return [
                    'dryRun'   => $options->dryRun,
                    'linkOnly' => $options->linkOnly,
                    'prune'    => $options->prune,
                    'force'    => $options->force,
                    'path'     => $options->path,
                ];
            })
            ->toMatchArray([
                'dryRun'   => false,
                'linkOnly' => false,
                'prune'    => false,
                'force'    => false,
                'path'     => null,
            ]);

        it('creates options with custom values')
            ->linksAndCovers(SyncOptions::class.'::__construct')
            ->expect(function () {
                $options = new SyncOptions(
                    dryRun: true,
                    linkOnly: true,
                    path: '/custom/path'
                );

                return [
                    'dryRun'   => $options->dryRun,
                    'linkOnly' => $options->linkOnly,
                    'path'     => $options->path,
                ];
            })
            ->toMatchArray([
                'dryRun'   => true,
                'linkOnly' => true,
                'path'     => '/custom/path',
            ]);

        it('throws exception when prune without force')
            ->linksAndCovers(SyncOptions::class.'::__construct')
            ->throws(InvalidArgumentException::class)
            ->expect(fn () => new SyncOptions(prune: true));

        it('allows prune with force')
            ->linksAndCovers(SyncOptions::class.'::__construct')
            ->expect(function () {
                $options = new SyncOptions(prune: true, force: true);

                return [
                    'prune' => $options->prune,
                    'force' => $options->force,
                ];
            })
            ->toMatchArray([
                'prune' => true,
                'force' => true,
            ]);
    });

    describe('SyncResult integration', function (): void {
        it('creates empty result')
            ->linksAndCovers(SyncResult::class.'::__construct')
            ->expect(function () {
                $result = new SyncResult();

                return [
                    'hasErrors'     => $result->hasErrors(),
                    'modifiedFiles' => $result->modifiedFiles,
                    'prunedFiles'   => $result->prunedFiles,
                ];
            })
            ->toMatchArray([
                'hasErrors'     => false,
                'modifiedFiles' => [],
                'prunedFiles'   => [],
            ]);

        it('creates result with errors')
            ->linksAndCovers(SyncResult::class.'::__construct')
            ->expect(function () {
                $result = new SyncResult(errors: ['Error 1', 'Error 2']);

                return [
                    'hasErrors'  => $result->hasErrors(),
                    'errorCount' => count($result->errors),
                ];
            })
            ->toMatchArray([
                'hasErrors'  => true,
                'errorCount' => 2,
            ]);

        it('creates result with modified files')
            ->linksAndCovers(SyncResult::class.'::__construct')
            ->expect(function () {
                $result = new SyncResult(
                    modifiedFiles: [
                        '/path/to/file1.php' => ['method1', 'method2'],
                        '/path/to/file2.php' => ['method3'],
                    ]
                );

                return [
                    'hasErrors'         => $result->hasErrors(),
                    'modifiedFileCount' => count($result->modifiedFiles),
                ];
            })
            ->toMatchArray([
                'hasErrors'         => false,
                'modifiedFileCount' => 2,
            ]);

        it('creates result with pruned files')
            ->linksAndCovers(SyncResult::class.'::__construct')
            ->expect(function () {
                $result = new SyncResult(
                    prunedFiles: [
                        '/path/to/file.php' => ['orphanMethod'],
                    ]
                );

                return [
                    'hasErrors'       => $result->hasErrors(),
                    'prunedFileCount' => count($result->prunedFiles),
                ];
            })
            ->toMatchArray([
                'hasErrors'       => false,
                'prunedFileCount' => 1,
            ]);
    });

    describe('fromArguments factory', function (): void {
        it('parses dry-run flag')
            ->linksAndCovers(SyncOptions::class.'::fromArguments')
            ->expect(function () {
                $options = SyncOptions::fromArguments(['--dry-run']);

                return $options->dryRun;
            })
            ->toBeTrue();

        it('parses link-only flag')
            ->linksAndCovers(SyncOptions::class.'::fromArguments')
            ->expect(function () {
                $options = SyncOptions::fromArguments(['--link-only']);

                return $options->linkOnly;
            })
            ->toBeTrue();

        it('parses path with value')
            ->linksAndCovers(SyncOptions::class.'::fromArguments')
            ->expect(function () {
                $options = SyncOptions::fromArguments(['--path=/src']);

                return $options->path;
            })
            ->toBe('/src');

        it('parses multiple flags')
            ->linksAndCovers(SyncOptions::class.'::fromArguments')
            ->expect(function () {
                $options = SyncOptions::fromArguments([
                    '--dry-run',
                    '--link-only',
                    '--path=/app',
                ]);

                return [
                    'dryRun'   => $options->dryRun,
                    'linkOnly' => $options->linkOnly,
                    'path'     => $options->path,
                ];
            })
            ->toMatchArray([
                'dryRun'   => true,
                'linkOnly' => true,
                'path'     => '/app',
            ]);

        it('ignores unknown arguments')
            ->linksAndCovers(SyncOptions::class.'::fromArguments')
            ->expect(function () {
                $options = SyncOptions::fromArguments([
                    '--unknown',
                    '--dry-run',
                    'positional',
                ]);

                return [
                    'dryRun'   => $options->dryRun,
                    'linkOnly' => $options->linkOnly,
                ];
            })
            ->toMatchArray([
                'dryRun'   => true,
                'linkOnly' => false,
            ]);

        it('returns empty options for empty arguments')
            ->linksAndCovers(SyncOptions::class.'::fromArguments')
            ->expect(function () {
                $options = SyncOptions::fromArguments([]);

                return [
                    'dryRun' => $options->dryRun,
                    'path'   => $options->path,
                ];
            })
            ->toMatchArray([
                'dryRun' => false,
                'path'   => null,
            ]);
    });
});
