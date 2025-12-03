<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

beforeEach(function (): void {
    TestLinkRegistry::resetInstance();
});

describe('TestLinkRegistry', function (): void {
    describe('singleton pattern', function (): void {
        it('returns the same instance', function (): void {
            $instance1 = TestLinkRegistry::getInstance();
            $instance2 = TestLinkRegistry::getInstance();

            expect($instance1)->toBe($instance2);
        });

        it('resets the instance', function (): void {
            $instance1 = TestLinkRegistry::getInstance();
            TestLinkRegistry::resetInstance();
            $instance2 = TestLinkRegistry::getInstance();

            expect($instance1)->not->toBe($instance2);
        });
    });

    describe('registerLink', function (): void {
        it('registers a link between test and method', function (): void {
            $registry = new TestLinkRegistry();

            $registry->registerLink('TestClass::testMethod', 'ProductionClass::method');

            expect($registry->hasMethod('ProductionClass::method'))->toBeTrue();
            expect($registry->hasTest('TestClass::testMethod'))->toBeTrue();
        });

        it('avoids duplicate links', function (): void {
            $registry = new TestLinkRegistry();

            $registry->registerLink('TestClass::testMethod', 'ProductionClass::method');
            $registry->registerLink('TestClass::testMethod', 'ProductionClass::method');

            expect($registry->count())->toBe(1);
        });

        it('allows multiple tests for one method', function (): void {
            $registry = new TestLinkRegistry();

            $registry->registerLink('TestClass::test1', 'ProductionClass::method');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method');

            $tests = $registry->getTestsForMethod('ProductionClass::method');

            expect($tests)->toHaveCount(2)
                ->toContain('TestClass::test1')
                ->toContain('TestClass::test2');
        });

        it('allows multiple methods for one test', function (): void {
            $registry = new TestLinkRegistry();

            $registry->registerLink('TestClass::testMethod', 'ProductionClass::method1');
            $registry->registerLink('TestClass::testMethod', 'ProductionClass::method2');

            $methods = $registry->getMethodsForTest('TestClass::testMethod');

            expect($methods)->toHaveCount(2)
                ->toContain('ProductionClass::method1')
                ->toContain('ProductionClass::method2');
        });
    });

    describe('getTestsForMethod', function (): void {
        it('returns tests for a registered method', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::testMethod', 'ProductionClass::method');

            $tests = $registry->getTestsForMethod('ProductionClass::method');

            expect($tests)->toBe(['TestClass::testMethod']);
        });

        it('returns empty array for unknown method', function (): void {
            $registry = new TestLinkRegistry();

            $tests = $registry->getTestsForMethod('Unknown::method');

            expect($tests)->toBe([]);
        });
    });

    describe('getMethodsForTest', function (): void {
        it('returns methods for a registered test', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::testMethod', 'ProductionClass::method');

            $methods = $registry->getMethodsForTest('TestClass::testMethod');

            expect($methods)->toBe(['ProductionClass::method']);
        });

        it('returns empty array for unknown test', function (): void {
            $registry = new TestLinkRegistry();

            $methods = $registry->getMethodsForTest('Unknown::test');

            expect($methods)->toBe([]);
        });
    });

    describe('getAllLinks', function (): void {
        it('returns all links grouped by method', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method2');

            $links = $registry->getAllLinks();

            expect($links)->toBe([
                'ProductionClass::method1' => ['TestClass::test1'],
                'ProductionClass::method2' => ['TestClass::test2'],
            ]);
        });

        it('returns empty array when no links', function (): void {
            $registry = new TestLinkRegistry();

            expect($registry->getAllLinks())->toBe([]);
        });
    });

    describe('getAllLinksByTest', function (): void {
        it('returns all links grouped by test', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test1', 'ProductionClass::method2');

            $links = $registry->getAllLinksByTest();

            expect($links)->toBe([
                'TestClass::test1' => ['ProductionClass::method1', 'ProductionClass::method2'],
            ]);
        });
    });

    describe('hasMethod and hasTest', function (): void {
        it('returns true for registered method', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test', 'ProductionClass::method');

            expect($registry->hasMethod('ProductionClass::method'))->toBeTrue();
            expect($registry->hasMethod('Unknown::method'))->toBeFalse();
        });

        it('returns true for registered test', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test', 'ProductionClass::method');

            expect($registry->hasTest('TestClass::test'))->toBeTrue();
            expect($registry->hasTest('Unknown::test'))->toBeFalse();
        });
    });

    describe('getAllMethods and getAllTests', function (): void {
        it('returns all registered methods', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method2');

            $methods = $registry->getAllMethods();

            expect($methods)->toContain('ProductionClass::method1')
                ->toContain('ProductionClass::method2');
        });

        it('returns all registered tests', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method2');

            $tests = $registry->getAllTests();

            expect($tests)->toContain('TestClass::test1')
                ->toContain('TestClass::test2');
        });
    });

    describe('clear', function (): void {
        it('clears all registered links', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test', 'ProductionClass::method');
            $registry->clear();

            expect($registry->count())->toBe(0);
            expect($registry->getAllLinks())->toBe([]);
        });
    });

    describe('count methods', function (): void {
        it('counts total links', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test3', 'ProductionClass::method2');

            expect($registry->count())->toBe(3);
        });

        it('counts unique methods', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test3', 'ProductionClass::method2');

            expect($registry->countMethods())->toBe(2);
        });

        it('counts unique tests', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test1', 'ProductionClass::method2');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method3');

            expect($registry->countTests())->toBe(2);
        });
    });

    describe('TestedBy links', function (): void {
        it('registers a TestedBy link', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test');

            expect($registry->hasTestedBy('ProductionClass::method'))->toBeTrue();
        });

        it('avoids duplicate TestedBy links', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test');
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test');

            $tests = $registry->getTestedByForMethod('ProductionClass::method');
            expect($tests)->toHaveCount(1);
        });

        it('allows multiple tests for one method via TestedBy', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test1');
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test2');

            $tests = $registry->getTestedByForMethod('ProductionClass::method');
            expect($tests)->toHaveCount(2)
                ->toContain('TestClass::test1')
                ->toContain('TestClass::test2');
        });

        it('returns empty array for unknown method TestedBy', function (): void {
            $registry = new TestLinkRegistry();

            $tests = $registry->getTestedByForMethod('Unknown::method');
            expect($tests)->toBe([]);
        });

        it('returns all TestedBy links', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerTestedBy('ProductionClass::method1', 'TestClass::test1');
            $registry->registerTestedBy('ProductionClass::method2', 'TestClass::test2');

            $links = $registry->getTestedByLinks();
            expect($links)->toBe([
                'ProductionClass::method1' => ['TestClass::test1'],
                'ProductionClass::method2' => ['TestClass::test2'],
            ]);
        });

        it('hasTestedBy returns false for unknown method', function (): void {
            $registry = new TestLinkRegistry();

            expect($registry->hasTestedBy('Unknown::method'))->toBeFalse();
        });

        it('clear also clears TestedBy links', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test');
            $registry->clear();

            expect($registry->hasTestedBy('ProductionClass::method'))->toBeFalse();
            expect($registry->getTestedByLinks())->toBe([]);
        });
    });
});
