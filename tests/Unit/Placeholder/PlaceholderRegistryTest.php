<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Placeholder\PlaceholderRegistry;

describe('PlaceholderRegistry', function (): void {
    describe('isPlaceholder', function (): void {
        it('returns true for valid single letter placeholder @A')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@A'))
            ->toBeTrue();

        it('returns true for valid placeholder @user-create')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@user-create'))
            ->toBeTrue();

        it('returns true for valid placeholder @UserCreate123')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@UserCreate123'))
            ->toBeTrue();

        it('returns true for valid placeholder with underscore @user_create')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@user_create'))
            ->toBeTrue();

        it('returns false for real class reference')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('App\\Services\\UserService::create'))
            ->toBeFalse();

        it('returns false for class::class syntax')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder("UserService::class.'::create'"))
            ->toBeFalse();

        it('returns false for @ followed by number')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@123'))
            ->toBeFalse();

        it('returns false for @ followed by invalid chars')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@invalid!'))
            ->toBeFalse();

        it('returns false for just @')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder('@'))
            ->toBeFalse();

        it('returns false for empty string')
            ->linksAndCovers(PlaceholderRegistry::class.'::isPlaceholder')
            ->expect(fn () => PlaceholderRegistry::isPlaceholder(''))
            ->toBeFalse();
    });

    describe('registerProductionPlaceholder', function (): void {
        it('stores production placeholder entry')
            ->linksAndCovers(PlaceholderRegistry::class.'::registerProductionPlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder(
                    '@A',
                    'App\\Services\\UserService',
                    'create',
                    '/path/to/UserService.php',
                    25
                );

                $entries = $registry->getProductionEntries('@A');

                return [
                    'count'      => count($entries),
                    'identifier' => $entries[0]->identifier,
                    'type'       => $entries[0]->type,
                ];
            })
            ->toMatchArray([
                'count'      => 1,
                'identifier' => 'App\\Services\\UserService::create',
                'type'       => 'production',
            ]);

        it('allows multiple production methods with same placeholder')
            ->linksAndCovers(PlaceholderRegistry::class.'::registerProductionPlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'UserService', 'create', '/path', 10);
                $registry->registerProductionPlaceholder('@A', 'UserService', 'update', '/path', 20);

                return count($registry->getProductionEntries('@A'));
            })
            ->toBe(2);
    });

    describe('registerTestPlaceholder', function (): void {
        it('stores test placeholder entry for Pest')
            ->linksAndCovers(PlaceholderRegistry::class.'::registerTestPlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder(
                    '@A',
                    'Tests\\Unit\\UserServiceTest::it creates user',
                    '/path/to/UserServiceTest.php',
                    15,
                    'pest'
                );

                $entries = $registry->getTestEntries('@A');

                return [
                    'count'     => count($entries),
                    'framework' => $entries[0]->framework,
                    'type'      => $entries[0]->type,
                ];
            })
            ->toMatchArray([
                'count'     => 1,
                'framework' => 'pest',
                'type'      => 'test',
            ]);

        it('stores test placeholder entry for PHPUnit')
            ->linksAndCovers(PlaceholderRegistry::class.'::registerTestPlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder(
                    '@A',
                    'Tests\\Unit\\UserServiceTest::testCreatesUser',
                    '/path/to/UserServiceTest.php',
                    15,
                    'phpunit'
                );

                $entries = $registry->getTestEntries('@A');

                return $entries[0]->framework;
            })
            ->toBe('phpunit');
    });

    describe('getAllPlaceholderIds', function (): void {
        it('returns all unique placeholder IDs from both production and test')
            ->linksAndCovers(PlaceholderRegistry::class.'::getAllPlaceholderIds')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'Class', 'method', '/path', 1);
                $registry->registerTestPlaceholder('@B', 'Test::test', '/path', 1, 'pest');
                $registry->registerProductionPlaceholder('@A', 'Class2', 'method2', '/path', 2);

                return $registry->getAllPlaceholderIds();
            })
            ->toContain('@A')
            ->toContain('@B')
            ->toHaveCount(2);

        it('returns empty array when no placeholders registered')
            ->linksAndCovers(PlaceholderRegistry::class.'::getAllPlaceholderIds')
            ->expect(fn () => (new PlaceholderRegistry())->getAllPlaceholderIds())
            ->toBe([]);

        it('returns sorted placeholder IDs')
            ->linksAndCovers(PlaceholderRegistry::class.'::getAllPlaceholderIds')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@C', 'Class', 'method', '/path', 1);
                $registry->registerTestPlaceholder('@A', 'Test::test', '/path', 1, 'pest');
                $registry->registerProductionPlaceholder('@B', 'Class2', 'method2', '/path', 2);

                return $registry->getAllPlaceholderIds();
            })
            ->toBe(['@A', '@B', '@C']);
    });

    describe('hasProductionEntries', function (): void {
        it('returns true when placeholder has production entries')
            ->linksAndCovers(PlaceholderRegistry::class.'::hasProductionEntries')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'Class', 'method', '/path', 1);

                return $registry->hasProductionEntries('@A');
            })
            ->toBeTrue();

        it('returns false when placeholder has no production entries')
            ->linksAndCovers(PlaceholderRegistry::class.'::hasProductionEntries')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder('@A', 'Test::test', '/path', 1, 'pest');

                return $registry->hasProductionEntries('@A');
            })
            ->toBeFalse();
    });

    describe('hasTestEntries', function (): void {
        it('returns true when placeholder has test entries')
            ->linksAndCovers(PlaceholderRegistry::class.'::hasTestEntries')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder('@A', 'Test::test', '/path', 1, 'pest');

                return $registry->hasTestEntries('@A');
            })
            ->toBeTrue();

        it('returns false when placeholder has no test entries')
            ->linksAndCovers(PlaceholderRegistry::class.'::hasTestEntries')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'Class', 'method', '/path', 1);

                return $registry->hasTestEntries('@A');
            })
            ->toBeFalse();
    });

    describe('getProductionEntryCount', function (): void {
        it('returns total count of production entries')
            ->linksAndCovers(PlaceholderRegistry::class.'::getProductionEntryCount')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'Class1', 'method1', '/path', 1);
                $registry->registerProductionPlaceholder('@A', 'Class1', 'method2', '/path', 2);
                $registry->registerProductionPlaceholder('@B', 'Class2', 'method1', '/path', 3);

                return $registry->getProductionEntryCount();
            })
            ->toBe(3);
    });

    describe('getTestEntryCount', function (): void {
        it('returns total count of test entries')
            ->linksAndCovers(PlaceholderRegistry::class.'::getTestEntryCount')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder('@A', 'Test1::test1', '/path', 1, 'pest');
                $registry->registerTestPlaceholder('@A', 'Test1::test2', '/path', 2, 'pest');
                $registry->registerTestPlaceholder('@B', 'Test2::test1', '/path', 3, 'phpunit');

                return $registry->getTestEntryCount();
            })
            ->toBe(3);
    });
});
