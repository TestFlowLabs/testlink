<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\Console\Command\PairCommand;
use TestFlowLabs\TestLink\Placeholder\PlaceholderRegistry;

describe('PairCommand', function (): void {
    beforeEach(function (): void {
        $this->command = new PairCommand();
        $this->parser  = new ArgumentParser();
        $this->output  = new Output();
    });

    describe('placeholder validation via PlaceholderRegistry', function (): void {
        it('accepts valid placeholder @A')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@A'))
            ->toBeTrue();

        it('accepts valid placeholder @user-create')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@user-create'))
            ->toBeTrue();

        it('accepts valid placeholder @UserCreate123')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@UserCreate123'))
            ->toBeTrue();

        it('accepts valid placeholder @user_create')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@user_create'))
            ->toBeTrue();

        it('rejects empty string')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder(''))
            ->toBeFalse();

        it('rejects @ alone')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@'))
            ->toBeFalse();

        it('rejects placeholder starting with number')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@123'))
            ->toBeFalse();

        it('rejects placeholder with special characters')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@invalid!'))
            ->toBeFalse();

        it('rejects placeholder without @')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('user-create'))
            ->toBeFalse();

        it('rejects class reference')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('UserService::class'))
            ->toBeFalse();

        it('rejects full method reference')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('App\\Services\\UserService::create'))
            ->toBeFalse();
    });

    describe('command execution', function (): void {
        it('returns int exit code')
            ->linksAndCovers(PairCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'pair', '--dry-run']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBeInt();

        it('accepts --dry-run flag')
            ->linksAndCovers(PairCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'pair', '--dry-run']);

                return $this->parser->hasOption('dry-run');
            })
            ->toBeTrue();

        it('accepts --placeholder option')
            ->linksAndCovers(PairCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'pair', '--placeholder=@A']);

                return $this->parser->getString('placeholder');
            })
            ->toBe('@A');

        it('accepts --path option')
            ->linksAndCovers(PairCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'pair', '--path=/src']);

                return $this->parser->getString('path');
            })
            ->toBe('/src');
    });

    describe('PlaceholderRegistry integration', function (): void {
        it('registers production placeholder correctly')
            ->linksAndCovers(PlaceholderRegistry::class.'::registerProductionPlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder(
                    '@test',
                    'App\\Services\\UserService',
                    'create',
                    '/path/to/UserService.php',
                    10
                );

                return [
                    'has_entries' => $registry->hasProductionEntries('@test'),
                    'entry_count' => count($registry->getProductionEntries('@test')),
                ];
            })
            ->toMatchArray([
                'has_entries' => true,
                'entry_count' => 1,
            ]);

        it('registers test placeholder correctly')
            ->linksAndCovers(PlaceholderRegistry::class.'::registerTestPlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder(
                    '@test',
                    'Tests\\Unit\\UserServiceTest::it creates user',
                    '/path/to/UserServiceTest.php',
                    15,
                    'pest'
                );

                return [
                    'has_entries' => $registry->hasTestEntries('@test'),
                    'entry_count' => count($registry->getTestEntries('@test')),
                ];
            })
            ->toMatchArray([
                'has_entries' => true,
                'entry_count' => 1,
            ]);

        it('returns all placeholder IDs sorted')
            ->linksAndCovers(PlaceholderRegistry::class.'::getAllPlaceholderIds')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@C', 'Class', 'method', '/path', 1);
                $registry->registerProductionPlaceholder('@A', 'Class', 'method', '/path', 2);
                $registry->registerTestPlaceholder('@B', 'Test::test', '/path', 3, 'pest');

                return $registry->getAllPlaceholderIds();
            })
            ->toBe(['@A', '@B', '@C']);

        it('detects orphan production placeholder (no test entries)')
            ->linksAndCovers(PlaceholderRegistry::class.'::hasTestEntries')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder(
                    '@orphan',
                    'App\\Services\\OrphanService',
                    'orphanMethod',
                    '/path/to/OrphanService.php',
                    10
                );

                return [
                    'has_production' => $registry->hasProductionEntries('@orphan'),
                    'has_test'       => $registry->hasTestEntries('@orphan'),
                    'is_orphan'      => !$registry->hasTestEntries('@orphan'),
                ];
            })
            ->toMatchArray([
                'has_production' => true,
                'has_test'       => false,
                'is_orphan'      => true,
            ]);

        it('detects orphan test placeholder (no production entries)')
            ->linksAndCovers(PlaceholderRegistry::class.'::hasProductionEntries')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder(
                    '@orphan',
                    'Tests\\Unit\\OrphanTest::it is orphan',
                    '/path/to/OrphanTest.php',
                    15,
                    'pest'
                );

                return [
                    'has_production' => $registry->hasProductionEntries('@orphan'),
                    'has_test'       => $registry->hasTestEntries('@orphan'),
                    'is_orphan'      => !$registry->hasProductionEntries('@orphan'),
                ];
            })
            ->toMatchArray([
                'has_production' => false,
                'has_test'       => true,
                'is_orphan'      => true,
            ]);

        it('counts N:M matching correctly')
            ->linksAndCovers(PlaceholderRegistry::class.'::getProductionEntries')
            ->expect(function () {
                $registry = new PlaceholderRegistry();

                // 2 production methods
                $registry->registerProductionPlaceholder('@A', 'Class', 'method1', '/path', 1);
                $registry->registerProductionPlaceholder('@A', 'Class', 'method2', '/path', 2);

                // 3 tests
                $registry->registerTestPlaceholder('@A', 'Test::test1', '/path', 1, 'pest');
                $registry->registerTestPlaceholder('@A', 'Test::test2', '/path', 2, 'pest');
                $registry->registerTestPlaceholder('@A', 'Test::test3', '/path', 3, 'phpunit');

                $prodCount = count($registry->getProductionEntries('@A'));
                $testCount = count($registry->getTestEntries('@A'));

                return [
                    'production_count' => $prodCount,
                    'test_count'       => $testCount,
                    'expected_links'   => $prodCount * $testCount,
                ];
            })
            ->toMatchArray([
                'production_count' => 2,
                'test_count'       => 3,
                'expected_links'   => 6,
            ]);
    });

    describe('entry counts', function (): void {
        it('counts total production entries')
            ->linksAndCovers(PlaceholderRegistry::class.'::getProductionEntryCount')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'Class', 'method1', '/path', 1);
                $registry->registerProductionPlaceholder('@A', 'Class', 'method2', '/path', 2);
                $registry->registerProductionPlaceholder('@B', 'Class', 'method3', '/path', 3);

                return $registry->getProductionEntryCount();
            })
            ->toBe(3);

        it('counts total test entries')
            ->linksAndCovers(PlaceholderRegistry::class.'::getTestEntryCount')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder('@A', 'Test::test1', '/path', 1, 'pest');
                $registry->registerTestPlaceholder('@A', 'Test::test2', '/path', 2, 'pest');

                return $registry->getTestEntryCount();
            })
            ->toBe(2);

        it('returns zero for empty registry')
            ->linksAndCovers(PlaceholderRegistry::class.'::getProductionEntryCount')
            ->expect(function () {
                $registry = new PlaceholderRegistry();

                return [
                    'production' => $registry->getProductionEntryCount(),
                    'test'       => $registry->getTestEntryCount(),
                ];
            })
            ->toMatchArray([
                'production' => 0,
                'test'       => 0,
            ]);
    });
});
