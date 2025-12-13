<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Placeholder\PlaceholderEntry;
use TestFlowLabs\TestLink\Placeholder\PlaceholderAction;

describe('PlaceholderAction', function (): void {
    beforeEach(function (): void {
        $this->productionEntry = new PlaceholderEntry(
            placeholder: '@A',
            identifier: 'App\\Services\\UserService::create',
            filePath: '/path/to/UserService.php',
            line: 25,
            type: 'production',
            framework: null,
        );

        $this->testEntry = new PlaceholderEntry(
            placeholder: '@A',
            identifier: 'Tests\\Unit\\UserServiceTest::it creates user',
            filePath: '/path/to/UserServiceTest.php',
            line: 15,
            type: 'test',
            framework: 'pest',
        );
    });

    describe('constructor', function (): void {
        it('creates action with placeholder and entries')
            ->expect(function () {
                $action = new PlaceholderAction(
                    placeholderId: '@A',
                    productionEntry: $this->productionEntry,
                    testEntry: $this->testEntry,
                );

                return $action->placeholderId;
            })
            ->toBe('@A');
    });

    describe('getProductionMethodIdentifier', function (): void {
        it('returns production identifier')
            ->linksAndCovers(PlaceholderAction::class.'::getProductionMethodIdentifier')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->getProductionMethodIdentifier();
            })
            ->toBe('App\\Services\\UserService::create');
    });

    describe('getTestIdentifier', function (): void {
        it('returns test identifier')
            ->linksAndCovers(PlaceholderAction::class.'::getTestIdentifier')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->getTestIdentifier();
            })
            ->toBe('Tests\\Unit\\UserServiceTest::it creates user');
    });

    describe('getProductionClassName', function (): void {
        it('returns production class name')
            ->linksAndCovers(PlaceholderAction::class.'::getProductionClassName')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->getProductionClassName();
            })
            ->toBe('App\\Services\\UserService');
    });

    describe('getProductionMethodName', function (): void {
        it('returns production method name')
            ->linksAndCovers(PlaceholderAction::class.'::getProductionMethodName')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->getProductionMethodName();
            })
            ->toBe('create');
    });

    describe('getTestClassName', function (): void {
        it('returns test class name')
            ->linksAndCovers(PlaceholderAction::class.'::getTestClassName')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->getTestClassName();
            })
            ->toBe('Tests\\Unit\\UserServiceTest');
    });

    describe('getTestMethodName', function (): void {
        it('returns test method name')
            ->linksAndCovers(PlaceholderAction::class.'::getTestMethodName')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->getTestMethodName();
            })
            ->toBe('it creates user');
    });

    describe('getProductionFilePath', function (): void {
        it('returns production file path')
            ->linksAndCovers(PlaceholderAction::class.'::getProductionFilePath')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->getProductionFilePath();
            })
            ->toBe('/path/to/UserService.php');
    });

    describe('getTestFilePath', function (): void {
        it('returns test file path')
            ->linksAndCovers(PlaceholderAction::class.'::getTestFilePath')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->getTestFilePath();
            })
            ->toBe('/path/to/UserServiceTest.php');
    });

    describe('isPestTest', function (): void {
        it('returns true for Pest test entry')
            ->linksAndCovers(PlaceholderAction::class.'::isPestTest')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->isPestTest();
            })
            ->toBeTrue();

        it('returns false for PHPUnit test entry')
            ->linksAndCovers(PlaceholderAction::class.'::isPestTest')
            ->expect(function () {
                $phpunitEntry = new PlaceholderEntry(
                    '@A', 'Tests\\Unit\\UserServiceTest::testCreatesUser', '/path', 15, 'test', 'phpunit'
                );
                $action = new PlaceholderAction('@A', $this->productionEntry, $phpunitEntry);

                return $action->isPestTest();
            })
            ->toBeFalse();
    });

    describe('isPhpUnitTest', function (): void {
        it('returns true for PHPUnit test entry')
            ->linksAndCovers(PlaceholderAction::class.'::isPhpUnitTest')
            ->expect(function () {
                $phpunitEntry = new PlaceholderEntry(
                    '@A', 'Tests\\Unit\\UserServiceTest::testCreatesUser', '/path', 15, 'test', 'phpunit'
                );
                $action = new PlaceholderAction('@A', $this->productionEntry, $phpunitEntry);

                return $action->isPhpUnitTest();
            })
            ->toBeTrue();

        it('returns false for Pest test entry')
            ->linksAndCovers(PlaceholderAction::class.'::isPhpUnitTest')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->isPhpUnitTest();
            })
            ->toBeFalse();
    });

    describe('useSeeTagOnProduction', function (): void {
        it('returns true when production entry has @@prefix')
            ->linksAndCovers(PlaceholderAction::class.'::useSeeTagOnProduction')
            ->expect(function () {
                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\UserService::create', '/path', 25, 'production', null, true
                );
                $testEntry = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\Test::testMethod', '/path', 15, 'test', 'phpunit', true
                );
                $action = new PlaceholderAction('@@A', $prodEntry, $testEntry);

                return $action->useSeeTagOnProduction();
            })
            ->toBeTrue();

        it('returns false when production entry has @prefix')
            ->linksAndCovers(PlaceholderAction::class.'::useSeeTagOnProduction')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->useSeeTagOnProduction();
            })
            ->toBeFalse();
    });

    describe('useSeeTagOnTest', function (): void {
        it('returns true when test entry has @@prefix')
            ->linksAndCovers(PlaceholderAction::class.'::useSeeTagOnTest')
            ->expect(function () {
                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\UserService::create', '/path', 25, 'production', null, true
                );
                $testEntry = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\Test::testMethod', '/path', 15, 'test', 'phpunit', true
                );
                $action = new PlaceholderAction('@@A', $prodEntry, $testEntry);

                return $action->useSeeTagOnTest();
            })
            ->toBeTrue();

        it('returns false when test entry has @prefix')
            ->linksAndCovers(PlaceholderAction::class.'::useSeeTagOnTest')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->useSeeTagOnTest();
            })
            ->toBeFalse();
    });

    describe('isSeeTagWithPest', function (): void {
        it('returns true when @@prefix is used with Pest test')
            ->linksAndCovers(PlaceholderAction::class.'::isSeeTagWithPest')
            ->expect(function () {
                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\UserService::create', '/path', 25, 'production', null, true
                );
                $testEntry = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\Test::it creates user', '/path', 15, 'test', 'pest', true
                );
                $action = new PlaceholderAction('@@A', $prodEntry, $testEntry);

                return $action->isSeeTagWithPest();
            })
            ->toBeTrue();

        it('returns false when @@prefix is used with PHPUnit test')
            ->linksAndCovers(PlaceholderAction::class.'::isSeeTagWithPest')
            ->expect(function () {
                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\UserService::create', '/path', 25, 'production', null, true
                );
                $testEntry = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\Test::testMethod', '/path', 15, 'test', 'phpunit', true
                );
                $action = new PlaceholderAction('@@A', $prodEntry, $testEntry);

                return $action->isSeeTagWithPest();
            })
            ->toBeFalse();

        it('returns false when @prefix is used with Pest test')
            ->linksAndCovers(PlaceholderAction::class.'::isSeeTagWithPest')
            ->expect(function () {
                $action = new PlaceholderAction('@A', $this->productionEntry, $this->testEntry);

                return $action->isSeeTagWithPest();
            })
            ->toBeFalse();
    });
});
