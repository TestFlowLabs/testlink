<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Runtime\TestLinkTrait;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

beforeEach(function (): void {
    TestLinkRegistry::resetInstance();
});

describe('TestLinkTrait', function (): void {
    it('can be used in a class', function (): void {
        $testCase = new class() {
            use TestLinkTrait;

            public function name(): string
            {
                return 'test method name';
            }
        };

        expect(method_exists($testCase, 'linksAndCovers'))->toBeTrue();
        expect(method_exists($testCase, 'links'))->toBeTrue();
        expect(method_exists($testCase, 'getLinkedMethods'))->toBeTrue();
    });

    it('registers links via linksAndCovers', function (): void {
        $testCase = new class() {
            use TestLinkTrait;

            public function name(): string
            {
                return 'it tests something';
            }
        };

        $testCase->linksAndCovers('App\\Service::method');

        $registry = TestLinkRegistry::getInstance();

        expect($registry->hasMethod('App\\Service::method'))->toBeTrue();
    });

    it('registers links via links method', function (): void {
        $testCase = new class() {
            use TestLinkTrait;

            public function name(): string
            {
                return 'it tests something else';
            }
        };

        $testCase->links('App\\Service::anotherMethod');

        $registry = TestLinkRegistry::getInstance();

        expect($registry->hasMethod('App\\Service::anotherMethod'))->toBeTrue();
    });

    it('registers multiple methods at once', function (): void {
        $testCase = new class() {
            use TestLinkTrait;

            public function name(): string
            {
                return 'it tests multiple methods';
            }
        };

        $testCase->linksAndCovers('App\\Service::method1', 'App\\Service::method2');

        $registry = TestLinkRegistry::getInstance();

        expect($registry->hasMethod('App\\Service::method1'))->toBeTrue();
        expect($registry->hasMethod('App\\Service::method2'))->toBeTrue();
    });

    it('returns linked methods via getLinkedMethods', function (): void {
        $testCase = new class() {
            use TestLinkTrait;

            public function name(): string
            {
                return 'it tracks coverage';
            }
        };

        $testCase->linksAndCovers('App\\Service::method1', 'App\\Service::method2');

        expect($testCase->getLinkedMethods())->toBe([
            'App\\Service::method1',
            'App\\Service::method2',
        ]);
    });

    it('returns self for fluent chaining', function (): void {
        $testCase = new class() {
            use TestLinkTrait;

            public function name(): string
            {
                return 'it chains';
            }
        };

        $result = $testCase->linksAndCovers('App\\Service::method');

        expect($result)->toBe($testCase);
    });

    it('uses getName fallback when name method is not available', function (): void {
        $testCase = new class() {
            use TestLinkTrait;

            public function getName(): string
            {
                return 'getName method';
            }
        };

        $testCase->linksAndCovers('App\\Service::method');

        $registry = TestLinkRegistry::getInstance();
        $tests    = $registry->getTestsForMethod('App\\Service::method');

        expect($tests[0])->toContain('getName method');
    });

    it('separates links with and without coverage', function (): void {
        $testCase = new class() {
            use TestLinkTrait;

            public function name(): string
            {
                return 'it separates links';
            }
        };

        $testCase->linksAndCovers('App\\Service::withCoverage');
        $testCase->links('App\\Service::withoutCoverage');

        expect($testCase->getLinkedWithCoverageMethods())->toBe(['App\\Service::withCoverage']);
        expect($testCase->getLinkOnlyMethods())->toBe(['App\\Service::withoutCoverage']);
        expect($testCase->getLinkedMethods())->toBe([
            'App\\Service::withoutCoverage',
            'App\\Service::withCoverage',
        ]);
    });
});
