<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

beforeEach(function (): void {
    TestLinkRegistry::resetInstance();
});

describe('TestLinkRegistry', function (): void {
    describe('registerLink', function (): void {
        it('registers bidirectional links')
            ->linksAndCovers(TestLinkRegistry::class.'::registerLink')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('Tests\Unit\UserTest::it creates user', 'App\Services\UserService::create');

                return $registry->getAllLinks();
            })
            ->toHaveKey('App\Services\UserService::create');

        it('registers links with coverage flag')
            ->linksAndCovers(TestLinkRegistry::class.'::registerLink')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1', withCoverage: true);
                $registry->registerLink('test1', 'method2', withCoverage: false);

                return [
                    'withCoverage'    => $registry->getCoverageMethodsForTest('test1'),
                    'withoutCoverage' => $registry->getLinkOnlyMethodsForTest('test1'),
                ];
            })
            ->toMatchArray([
                'withCoverage'    => ['method1'],
                'withoutCoverage' => ['method2'],
            ]);
    });

    describe('getTestsForMethod', function (): void {
        it('returns tests for a method')
            ->linksAndCovers(TestLinkRegistry::class.'::getTestsForMethod')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1');
                $registry->registerLink('test2', 'method1');

                return $registry->getTestsForMethod('method1');
            })
            ->toHaveCount(2)
            ->toContain('test1')
            ->toContain('test2');
    });

    describe('getMethodsForTest', function (): void {
        it('returns methods for a test')
            ->linksAndCovers(TestLinkRegistry::class.'::getMethodsForTest')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1');
                $registry->registerLink('test1', 'method2');

                return $registry->getMethodsForTest('test1');
            })
            ->toHaveCount(2)
            ->toContain('method1')
            ->toContain('method2');
    });

    describe('hasLinkCoverage', function (): void {
        it('checks if a link has coverage enabled')
            ->linksAndCovers(TestLinkRegistry::class.'::hasLinkCoverage')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1', withCoverage: true);
                $registry->registerLink('test1', 'method2', withCoverage: false);

                return [
                    'hasCoverage'  => $registry->hasLinkCoverage('test1', 'method1'),
                    'noCoverage'   => $registry->hasLinkCoverage('test1', 'method2'),
                    'defaultsTrue' => $registry->hasLinkCoverage('test1', 'nonexistent'),
                ];
            })
            ->toMatchArray([
                'hasCoverage'  => true,
                'noCoverage'   => false,
                'defaultsTrue' => true,
            ]);
    });

    describe('hasMethod', function (): void {
        it('checks if method has any registered tests')
            ->linksAndCovers(TestLinkRegistry::class.'::hasMethod')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1');

                return [
                    'exists'    => $registry->hasMethod('method1'),
                    'notExists' => $registry->hasMethod('method2'),
                ];
            })
            ->toMatchArray([
                'exists'    => true,
                'notExists' => false,
            ]);
    });

    describe('hasTest', function (): void {
        it('checks if test has any registered methods')
            ->linksAndCovers(TestLinkRegistry::class.'::hasTest')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1');

                return [
                    'exists'    => $registry->hasTest('test1'),
                    'notExists' => $registry->hasTest('test2'),
                ];
            })
            ->toMatchArray([
                'exists'    => true,
                'notExists' => false,
            ]);
    });

    describe('getInstance', function (): void {
        it('returns singleton instance')
            ->linksAndCovers(TestLinkRegistry::class.'::getInstance')
            ->expect(fn () => TestLinkRegistry::getInstance())
            ->toBeInstanceOf(TestLinkRegistry::class);

        it('returns same instance on multiple calls')
            ->linksAndCovers(TestLinkRegistry::class.'::getInstance')
            ->expect(function () {
                $instance1 = TestLinkRegistry::getInstance();
                $instance2 = TestLinkRegistry::getInstance();

                return $instance1 === $instance2;
            })
            ->toBeTrue();
    });

    describe('clear', function (): void {
        it('clears all registered links')
            ->linksAndCovers(TestLinkRegistry::class.'::clear')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1');
                $registry->clear();

                return $registry->count();
            })
            ->toBe(0);
    });

    describe('count', function (): void {
        it('returns the total number of unique links')
            ->linksAndCovers(TestLinkRegistry::class.'::count')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1');
                $registry->registerLink('test2', 'method1');
                $registry->registerLink('test1', 'method2');

                return $registry->count();
            })
            ->toBe(3);
    });

    describe('countMethods', function (): void {
        it('returns the number of unique methods with links')
            ->linksAndCovers(TestLinkRegistry::class.'::countMethods')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1');
                $registry->registerLink('test2', 'method1');
                $registry->registerLink('test1', 'method2');

                return $registry->countMethods();
            })
            ->toBe(2);
    });

    describe('countTests', function (): void {
        it('returns the number of unique tests with links')
            ->linksAndCovers(TestLinkRegistry::class.'::countTests')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('test1', 'method1');
                $registry->registerLink('test2', 'method1');
                $registry->registerLink('test1', 'method2');

                return $registry->countTests();
            })
            ->toBe(2);
    });
});
