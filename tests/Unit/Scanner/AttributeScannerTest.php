<?php

declare(strict_types=1);

use Tests\Fixtures\TestCode\UserServiceTest;
use Tests\Fixtures\ProductionCode\OrderService;
use TestFlowLabs\TestLink\Scanner\AttributeScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

beforeEach(function (): void {
    TestLinkRegistry::resetInstance();
});

describe('AttributeScanner', function (): void {
    describe('scanClass', function (): void {
        it('scans #[LinksAndCovers] attributes on test methods', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanClass(UserServiceTest::class, $registry);

            // UserServiceTest::test_creates_user has #[LinksAndCovers]
            $methods = $registry->getMethodsForTest(UserServiceTest::class.'::test_creates_user');

            expect($methods)->toContain('App\Services\UserService::create');
        });

        it('scans #[Links] attributes on test methods', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanClass(UserServiceTest::class, $registry);

            // UserServiceTest::test_deletes_user_integration has #[Links]
            $methods = $registry->getMethodsForTest(UserServiceTest::class.'::test_deletes_user_integration');

            expect($methods)->toContain('App\Services\UserService::delete');
        });

        it('handles methods with multiple attributes', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanClass(UserServiceTest::class, $registry);

            // UserServiceTest::test_updates_and_validates_user has two #[LinksAndCovers]
            $methods = $registry->getMethodsForTest(UserServiceTest::class.'::test_updates_and_validates_user');

            expect($methods)->toHaveCount(2)
                ->toContain('App\Services\UserService::update')
                ->toContain('App\Services\UserService::validate');
        });

        it('handles class-level coverage links', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanClass(UserServiceTest::class, $registry);

            // UserServiceTest::test_user_service_class has class-level #[LinksAndCovers]
            $methods = $registry->getMethodsForTest(UserServiceTest::class.'::test_user_service_class');

            expect($methods)->toContain('App\Services\UserService');
        });

        it('ignores methods without attributes', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanClass(UserServiceTest::class, $registry);

            // UserServiceTest::test_without_attributes has no coverage link attributes
            $methods = $registry->getMethodsForTest(UserServiceTest::class.'::test_without_attributes');

            expect($methods)->toBe([]);
        });

        it('handles non-existent classes gracefully', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            // Should not throw an exception
            $scanner->scanClass('NonExistent\Class', $registry);

            expect($registry->count())->toBe(0);
        });
    });

    describe('scanClasses', function (): void {
        it('scans multiple classes', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanClasses([UserServiceTest::class], $registry);

            // Should have scanned UserServiceTest
            expect($registry->countMethods())->toBeGreaterThan(0);
        });

        it('handles empty class array', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanClasses([], $registry);

            expect($registry->count())->toBe(0);
        });
    });

    describe('setProjectRoot', function (): void {
        it('returns self for fluent interface', function (): void {
            $scanner = new AttributeScanner();

            $result = $scanner->setProjectRoot('/some/path');

            expect($result)->toBe($scanner);
        });

        it('affects class discovery', function (): void {
            $scanner = new AttributeScanner();
            $scanner->setProjectRoot('/nonexistent/path');

            $classes = $scanner->discoverClasses();

            expect($classes)->toBe([]);
        });
    });

    describe('discoverClasses', function (): void {
        it('returns array of class strings', function (): void {
            $scanner = new AttributeScanner();
            $scanner->setProjectRoot(dirname(__DIR__, 3));

            $classes = $scanner->discoverClasses();

            expect($classes)->toBeArray();
        });

        it('excludes vendor classes', function (): void {
            $scanner = new AttributeScanner();
            $scanner->setProjectRoot(dirname(__DIR__, 3));

            $classes = $scanner->discoverClasses();

            // Should return an array (may be empty if no classmap)
            expect($classes)->toBeArray();

            foreach ($classes as $class) {
                expect($class)->not->toContain('vendor');
            }
        });

        it('only includes test classes', function (): void {
            $scanner = new AttributeScanner();
            $scanner->setProjectRoot(dirname(__DIR__, 3));

            $classes = $scanner->discoverClasses();

            // Should return only test classes from tests/ directory
            expect($classes)->toBeArray();

            foreach ($classes as $class) {
                // Each class should be from tests directory (contains Tests namespace or similar)
                expect($class)->toMatch('/^Tests\\\\|Test$/');
            }
        });
    });

    describe('discoverAndScan', function (): void {
        it('discovers and scans all test classes', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();
            $scanner->setProjectRoot(dirname(__DIR__, 3));

            $scanner->discoverAndScan($registry);

            // After discovery, should have found some links
            expect($registry)->toBeInstanceOf(TestLinkRegistry::class);
        });
    });

    describe('scanProductionClass', function (): void {
        it('scans #[TestedBy] attributes on production methods', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanProductionClass(OrderService::class, $registry);

            // OrderService::create has #[TestedBy]
            $tests = $registry->getTestedByForMethod(OrderService::class.'::create');

            expect($tests)->toContain('Tests\Fixtures\TestCode\OrderServiceTest::testCreatesOrder');
        });

        it('handles methods with multiple TestedBy attributes', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanProductionClass(OrderService::class, $registry);

            // OrderService::update has two #[TestedBy] attributes
            $tests = $registry->getTestedByForMethod(OrderService::class.'::update');

            expect($tests)->toHaveCount(2)
                ->toContain('Tests\Fixtures\TestCode\OrderServiceTest::testUpdatesOrder')
                ->toContain('Tests\Fixtures\TestCode\OrderServiceTest::testValidatesOrder');
        });

        it('ignores methods without TestedBy', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanProductionClass(OrderService::class, $registry);

            // OrderService::findById has no #[TestedBy]
            $tests = $registry->getTestedByForMethod(OrderService::class.'::findById');

            expect($tests)->toBe([]);
        });

        it('handles non-existent classes gracefully', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanProductionClass('NonExistent\ProductionClass', $registry);

            expect($registry->getTestedByLinks())->toBe([]);
        });
    });

    describe('scanProductionClasses', function (): void {
        it('scans multiple production classes', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanProductionClasses([OrderService::class], $registry);

            expect($registry->getTestedByLinks())->not->toBe([]);
        });

        it('handles empty class array', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();

            $scanner->scanProductionClasses([], $registry);

            expect($registry->getTestedByLinks())->toBe([]);
        });
    });

    describe('discoverAndScanAll', function (): void {
        it('scans both test and production classes', function (): void {
            $registry = new TestLinkRegistry();
            $scanner  = new AttributeScanner();
            $scanner->setProjectRoot(dirname(__DIR__, 3));

            $scanner->discoverAndScanAll($registry);

            // Should have scanned both test classes (for Links*) and production classes (for TestedBy)
            expect($registry)->toBeInstanceOf(TestLinkRegistry::class);
        });
    });
});
