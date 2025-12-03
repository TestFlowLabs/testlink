<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Console\ArgumentParser;

describe('ArgumentParser', function (): void {
    beforeEach(function (): void {
        $this->parser = new ArgumentParser();
    });

    describe('parse', function (): void {
        it('extracts command name')
            ->expect(function () {
                $this->parser->parse(['script.php', 'report']);

                return $this->parser->getCommand();
            })
            ->toBe('report');

        it('extracts positional arguments')
            ->expect(function () {
                $this->parser->parse(['script.php', 'sync', 'path/to/file']);

                return $this->parser->getArguments();
            })
            ->toBe(['path/to/file']);

        it('returns null command when no arguments')
            ->expect(function () {
                $this->parser->parse(['script.php']);

                return $this->parser->getCommand();
            })
            ->toBeNull();
    });

    describe('long options', function (): void {
        it('parses --flag as boolean true')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--dry-run']);

                return $this->parser->getOption('dry-run');
            })
            ->toBeTrue();

        it('parses --option=value')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--path=/tmp']);

                return $this->parser->getOption('path');
            })
            ->toBe('/tmp');

        it('parses multiple long options')
            ->expect(function () {
                $this->parser->parse(['script.php', 'sync', '--dry-run', '--force', '--path=tests']);

                return [
                    'dry-run' => $this->parser->getOption('dry-run'),
                    'force'   => $this->parser->getOption('force'),
                    'path'    => $this->parser->getOption('path'),
                ];
            })
            ->toMatchArray([
                'dry-run' => true,
                'force'   => true,
                'path'    => 'tests',
            ]);
    });

    describe('short options', function (): void {
        it('parses -f as boolean true')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '-f']);

                return $this->parser->getOption('f');
            })
            ->toBeTrue();

        it('parses combined short options -abc')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '-abc']);

                return [
                    'a' => $this->parser->getOption('a'),
                    'b' => $this->parser->getOption('b'),
                    'c' => $this->parser->getOption('c'),
                ];
            })
            ->toMatchArray([
                'a' => true,
                'b' => true,
                'c' => true,
            ]);

        it('parses -p=value')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '-p=/tmp']);

                return $this->parser->getOption('p');
            })
            ->toBe('/tmp');
    });

    describe('hasOption', function (): void {
        it('returns true when option exists')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--verbose']);

                return $this->parser->hasOption('verbose');
            })
            ->toBeTrue();

        it('returns false when option does not exist')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command']);

                return $this->parser->hasOption('verbose');
            })
            ->toBeFalse();
    });

    describe('getOption', function (): void {
        it('returns default when option does not exist')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command']);

                return $this->parser->getOption('missing', 'default');
            })
            ->toBe('default');

        it('returns null when no default provided')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command']);

                return $this->parser->getOption('missing');
            })
            ->toBeNull();
    });

    describe('getOptions', function (): void {
        it('returns all parsed options')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--foo', '--bar=baz']);

                return $this->parser->getOptions();
            })
            ->toMatchArray([
                'foo' => true,
                'bar' => 'baz',
            ]);
    });

    describe('getBool', function (): void {
        it('returns true for flag option')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--verbose']);

                return $this->parser->getBool('verbose');
            })
            ->toBeTrue();

        it('returns false for missing option')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command']);

                return $this->parser->getBool('verbose');
            })
            ->toBeFalse();

        it('returns true for string "true"')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--enabled=true']);

                return $this->parser->getBool('enabled');
            })
            ->toBeTrue();

        it('returns true for string "1"')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--enabled=1']);

                return $this->parser->getBool('enabled');
            })
            ->toBeTrue();

        it('returns true for string "yes"')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--enabled=yes']);

                return $this->parser->getBool('enabled');
            })
            ->toBeTrue();

        it('returns false for other string values')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--enabled=false']);

                return $this->parser->getBool('enabled');
            })
            ->toBeFalse();
    });

    describe('getString', function (): void {
        it('returns string value')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--path=/tmp']);

                return $this->parser->getString('path');
            })
            ->toBe('/tmp');

        it('returns default when option is missing')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command']);

                return $this->parser->getString('path', '/default');
            })
            ->toBe('/default');

        it('returns default when option is boolean true')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command', '--verbose']);

                return $this->parser->getString('verbose', 'fallback');
            })
            ->toBe('fallback');

        it('returns null when missing and no default')
            ->expect(function () {
                $this->parser->parse(['script.php', 'command']);

                return $this->parser->getString('path');
            })
            ->toBeNull();
    });
});
