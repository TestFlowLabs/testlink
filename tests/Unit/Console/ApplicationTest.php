<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Console\Application;

describe('Application', function (): void {
    beforeEach(function (): void {
        $this->app = new Application();
    });

    describe('help and version exit codes', function (): void {
        it('returns 0 when no command provided (shows help)')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink']))
            ->toBe(0);

        it('returns 0 with --help flag')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', '--help']))
            ->toBe(0);

        it('returns 0 with -h flag')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', '-h']))
            ->toBe(0);

        it('returns 0 with --version flag')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', '--version']))
            ->toBe(0);

        it('returns 0 with -v flag')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', '-v']))
            ->toBe(0);

        it('returns 0 for command-specific help')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'report', '--help']))
            ->toBe(0);
    });

    describe('unknown command', function (): void {
        it('returns exit code 1 for unknown command')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'unknown-command']))
            ->toBe(1);

        it('returns exit code 1 for another unknown command')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'nonexistent']))
            ->toBe(1);

        it('returns exit code 1 for typo in command name')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'reprot']))
            ->toBe(1);
    });

    describe('command help exit codes', function (): void {
        it('returns 0 for validate --help')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'validate', '--help']))
            ->toBe(0);

        it('returns 0 for sync --help')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'sync', '--help']))
            ->toBe(0);

        it('returns 0 for pair --help')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'pair', '--help']))
            ->toBe(0);

        it('returns 0 for report -h')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'report', '-h']))
            ->toBe(0);
    });

    describe('global options', function (): void {
        it('accepts --verbose flag with help')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', '--help', '--verbose']))
            ->toBe(0);

        it('accepts --no-color flag with help')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', '--help', '--no-color']))
            ->toBe(0);

        it('accepts combined flags')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', '-h', '--verbose', '--no-color']))
            ->toBe(0);
    });

    describe('path validation', function (): void {
        it('returns exit code 1 for invalid path with report command')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'report', '--path=/nonexistent/path']))
            ->toBe(1);

        it('returns exit code 1 for invalid path with validate command')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'validate', '--path=/nonexistent/path']))
            ->toBe(1);

        it('returns exit code 1 for invalid path with sync command')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'sync', '--path=/nonexistent/path']))
            ->toBe(1);

        it('returns exit code 1 for invalid path with pair command')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'pair', '--path=/nonexistent/path']))
            ->toBe(1);

        it('returns exit code 0 for valid path')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'report', '--path=/tmp']))
            ->toBe(0);

        it('returns exit code 0 when no path provided')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'report']))
            ->toBe(0);
    });

    describe('valid commands execute without throwing', function (): void {
        it('report command executes')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'report']))
            ->toBe(0);

        it('validate command executes')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'validate']))
            ->toBeInt();

        it('sync --dry-run command executes')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'sync', '--dry-run']))
            ->toBeInt();

        it('pair --dry-run command executes')
            ->linksAndCovers(Application::class.'::run')
            ->expect(fn () => $this->app->run(['testlink', 'pair', '--dry-run']))
            ->toBeInt();
    });
});
