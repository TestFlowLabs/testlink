<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Validator\LinkValidator;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

beforeEach(function (): void {
    TestLinkRegistry::resetInstance();
});

describe('LinkValidator', function (): void {
    describe('validate', function (): void {
        it('returns valid when no duplicates exist', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            // Different links in each registry (no duplicates)
            $attributeRegistry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $runtimeRegistry->registerLink('TestClass::test2', 'ProductionClass::method2');

            $validator = new LinkValidator();
            $result    = $validator->validate($attributeRegistry, $runtimeRegistry);

            expect($result['valid'])->toBeTrue();
            expect($result['duplicates'])->toBe([]);
            expect($result['totalLinks'])->toBe(2);
        });

        it('returns valid when both registries are empty', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            $validator = new LinkValidator();
            $result    = $validator->validate($attributeRegistry, $runtimeRegistry);

            expect($result['valid'])->toBeTrue();
            expect($result['totalLinks'])->toBe(0);
        });

        it('finds duplicate links between registries', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            // Same link in both registries (duplicate)
            $attributeRegistry->registerLink('TestClass::test', 'ProductionClass::method');
            $runtimeRegistry->registerLink('TestClass::test', 'ProductionClass::method');

            $validator = new LinkValidator();
            $result    = $validator->validate($attributeRegistry, $runtimeRegistry);

            expect($result['valid'])->toBeFalse();
            expect($result['duplicates'])->toHaveCount(1);
            expect($result['duplicates'][0])->toBe([
                'test'   => 'TestClass::test',
                'method' => 'ProductionClass::method',
            ]);
        });

        it('counts total links from both registries', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            $attributeRegistry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $attributeRegistry->registerLink('TestClass::test2', 'ProductionClass::method2');
            $runtimeRegistry->registerLink('TestClass::test3', 'ProductionClass::method3');

            $validator = new LinkValidator();
            $result    = $validator->validate($attributeRegistry, $runtimeRegistry);

            expect($result['totalLinks'])->toBe(3);
        });
    });

    describe('findDuplicates', function (): void {
        it('returns empty when no duplicates', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            $attributeRegistry->registerLink('TestClass::test1', 'ProductionClass::method');
            $runtimeRegistry->registerLink('TestClass::test2', 'ProductionClass::method');

            $validator  = new LinkValidator();
            $duplicates = $validator->findDuplicates($attributeRegistry, $runtimeRegistry);

            expect($duplicates)->toBe([]);
        });

        it('returns duplicates when same test links to same method in both', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            $attributeRegistry->registerLink('TestClass::test', 'ProductionClass::method');
            $runtimeRegistry->registerLink('TestClass::test', 'ProductionClass::method');

            $validator  = new LinkValidator();
            $duplicates = $validator->findDuplicates($attributeRegistry, $runtimeRegistry);

            expect($duplicates)->toHaveCount(1);
            expect($duplicates[0]['test'])->toBe('TestClass::test');
            expect($duplicates[0]['method'])->toBe('ProductionClass::method');
        });

        it('finds multiple duplicates', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            $attributeRegistry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $attributeRegistry->registerLink('TestClass::test2', 'ProductionClass::method2');
            $runtimeRegistry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $runtimeRegistry->registerLink('TestClass::test2', 'ProductionClass::method2');

            $validator  = new LinkValidator();
            $duplicates = $validator->findDuplicates($attributeRegistry, $runtimeRegistry);

            expect($duplicates)->toHaveCount(2);
        });
    });

    describe('getAllLinks', function (): void {
        it('combines links from both registries', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            $attributeRegistry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $runtimeRegistry->registerLink('TestClass::test2', 'ProductionClass::method2');

            $validator = new LinkValidator();
            $allLinks  = $validator->getAllLinks($attributeRegistry, $runtimeRegistry);

            expect($allLinks)->toHaveCount(2);
            expect($allLinks['ProductionClass::method1'])->toContain('TestClass::test1');
            expect($allLinks['ProductionClass::method2'])->toContain('TestClass::test2');
        });

        it('deduplicates links across registries', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            // Same link in both
            $attributeRegistry->registerLink('TestClass::test', 'ProductionClass::method');
            $runtimeRegistry->registerLink('TestClass::test', 'ProductionClass::method');

            $validator = new LinkValidator();
            $allLinks  = $validator->getAllLinks($attributeRegistry, $runtimeRegistry);

            expect($allLinks)->toHaveCount(1);
            expect($allLinks['ProductionClass::method'])->toHaveCount(1);
        });
    });

    describe('isValid', function (): void {
        it('returns true when no duplicates', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            $attributeRegistry->registerLink('TestClass::test1', 'ProductionClass::method');
            $runtimeRegistry->registerLink('TestClass::test2', 'ProductionClass::method');

            $validator = new LinkValidator();

            expect($validator->isValid($attributeRegistry, $runtimeRegistry))->toBeTrue();
        });

        it('returns false when duplicates exist', function (): void {
            $attributeRegistry = new TestLinkRegistry();
            $runtimeRegistry   = new TestLinkRegistry();

            $attributeRegistry->registerLink('TestClass::test', 'ProductionClass::method');
            $runtimeRegistry->registerLink('TestClass::test', 'ProductionClass::method');

            $validator = new LinkValidator();

            expect($validator->isValid($attributeRegistry, $runtimeRegistry))->toBeFalse();
        });
    });

    describe('validateBidirectional', function (): void {
        it('returns valid when all links are synchronized', function (): void {
            $registry = new TestLinkRegistry();

            // Test links to method
            $registry->registerLink('TestClass::test', 'ProductionClass::method');

            // TestedBy on production points back to test
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test');

            $validator = new LinkValidator();
            $result    = $validator->validateBidirectional($registry);

            expect($result['valid'])->toBeTrue();
            expect($result['missingTestedBy'])->toBe([]);
            expect($result['orphanTestedBy'])->toBe([]);
        });

        it('finds missing TestedBy attributes', function (): void {
            $registry = new TestLinkRegistry();

            // Test links to method but no TestedBy
            $registry->registerLink('TestClass::test', 'ProductionClass::method');

            $validator = new LinkValidator();
            $result    = $validator->validateBidirectional($registry);

            expect($result['valid'])->toBeFalse();
            expect($result['missingTestedBy'])->toHaveCount(1);
            expect($result['missingTestedBy'][0])->toBe([
                'method' => 'ProductionClass::method',
                'test'   => 'TestClass::test',
            ]);
        });

        it('finds orphan TestedBy attributes', function (): void {
            $registry = new TestLinkRegistry();

            // TestedBy exists but no test links to it
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test');

            $validator = new LinkValidator();
            $result    = $validator->validateBidirectional($registry);

            expect($result['valid'])->toBeFalse();
            expect($result['orphanTestedBy'])->toHaveCount(1);
            expect($result['orphanTestedBy'][0])->toBe([
                'method' => 'ProductionClass::method',
                'test'   => 'TestClass::test',
            ]);
        });

        it('finds both missing and orphan TestedBy', function (): void {
            $registry = new TestLinkRegistry();

            // Test links to method1 (missing TestedBy)
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');

            // TestedBy for method2 points to non-existent test (orphan)
            $registry->registerTestedBy('ProductionClass::method2', 'TestClass::test2');

            $validator = new LinkValidator();
            $result    = $validator->validateBidirectional($registry);

            expect($result['valid'])->toBeFalse();
            expect($result['missingTestedBy'])->toHaveCount(1);
            expect($result['orphanTestedBy'])->toHaveCount(1);
        });

        it('counts total test links and TestedBy links', function (): void {
            $registry = new TestLinkRegistry();

            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method2');
            $registry->registerTestedBy('ProductionClass::method1', 'TestClass::test1');

            $validator = new LinkValidator();
            $result    = $validator->validateBidirectional($registry);

            expect($result['totalTestLinks'])->toBe(2);
            expect($result['totalTestedByLinks'])->toBe(1);
        });
    });

    describe('isBidirectionalValid', function (): void {
        it('returns true when all links are synchronized', function (): void {
            $registry = new TestLinkRegistry();

            $registry->registerLink('TestClass::test', 'ProductionClass::method');
            $registry->registerTestedBy('ProductionClass::method', 'TestClass::test');

            $validator = new LinkValidator();

            expect($validator->isBidirectionalValid($registry))->toBeTrue();
        });

        it('returns false when links are not synchronized', function (): void {
            $registry = new TestLinkRegistry();

            $registry->registerLink('TestClass::test', 'ProductionClass::method');
            // No TestedBy

            $validator = new LinkValidator();

            expect($validator->isBidirectionalValid($registry))->toBeFalse();
        });
    });
});
