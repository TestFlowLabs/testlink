<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Placeholder\PlaceholderScanner;
use TestFlowLabs\TestLink\Console\Command\ValidateCommand;
use TestFlowLabs\TestLink\Placeholder\PlaceholderRegistry;
use Tests\Fixtures\Placeholder\Production\PlaceholderUserService;

describe('ValidateCommand', function (): void {
    describe('placeholder detection', function (): void {
        it('detects placeholders from production fixture class')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                // Scan the fixture class for placeholders
                $registry = new PlaceholderRegistry();
                $scanner  = new PlaceholderScanner();
                $scanner->scanProductionClass(PlaceholderUserService::class, $registry);

                $placeholderIds = $registry->getAllPlaceholderIds();

                return [
                    'has_user_create' => in_array('@user-create', $placeholderIds, true),
                    'has_A'           => in_array('@A', $placeholderIds, true),
                    'has_B'           => in_array('@B', $placeholderIds, true),
                ];
            })
            ->toMatchArray([
                'has_user_create' => true,
                'has_A'           => true,
                'has_B'           => true,
            ]);

        it('counts production entries correctly for each placeholder')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $scanner  = new PlaceholderScanner();
                $scanner->scanProductionClass(PlaceholderUserService::class, $registry);

                return [
                    'user_create_count' => count($registry->getProductionEntries('@user-create')),
                    'A_count'           => count($registry->getProductionEntries('@A')),
                    'B_count'           => count($registry->getProductionEntries('@B')),
                ];
            })
            ->toMatchArray([
                'user_create_count' => 1, // create method
                'A_count'           => 1, // multiTested method
                'B_count'           => 1, // multiTested method
            ]);
    });

    describe('PlaceholderRegistry integration', function (): void {
        it('provides production and test counts for JSON output')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $registry = new PlaceholderRegistry();

                // Simulate production placeholder
                $registry->registerProductionPlaceholder(
                    '@test-placeholder',
                    'App\\Services\\TestService',
                    'testMethod',
                    '/path/to/TestService.php',
                    10
                );

                // Simulate test placeholder
                $registry->registerTestPlaceholder(
                    '@test-placeholder',
                    'Tests\\Unit\\TestServiceTest::it works',
                    '/path/to/TestServiceTest.php',
                    15,
                    'pest'
                );

                $placeholderIds = $registry->getAllPlaceholderIds();
                $id             = $placeholderIds[0];

                $result = [
                    'id'              => $id,
                    'productionCount' => count($registry->getProductionEntries($id)),
                    'testCount'       => count($registry->getTestEntries($id)),
                ];

                return $result;
            })
            ->toMatchArray([
                'id'              => '@test-placeholder',
                'productionCount' => 1,
                'testCount'       => 1,
            ]);

        it('handles orphan production placeholder (no test entries)')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $registry = new PlaceholderRegistry();

                // Only production placeholder, no test
                $registry->registerProductionPlaceholder(
                    '@orphan-prod',
                    'App\\Services\\OrphanService',
                    'orphanMethod',
                    '/path/to/OrphanService.php',
                    10
                );

                $id = '@orphan-prod';

                return [
                    'productionCount' => count($registry->getProductionEntries($id)),
                    'testCount'       => count($registry->getTestEntries($id)),
                    'isOrphan'        => !$registry->hasTestEntries($id),
                ];
            })
            ->toMatchArray([
                'productionCount' => 1,
                'testCount'       => 0,
                'isOrphan'        => true,
            ]);

        it('handles orphan test placeholder (no production entries)')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $registry = new PlaceholderRegistry();

                // Only test placeholder, no production
                $registry->registerTestPlaceholder(
                    '@orphan-test',
                    'Tests\\Unit\\OrphanTest::it is orphan',
                    '/path/to/OrphanTest.php',
                    15,
                    'pest'
                );

                $id = '@orphan-test';

                return [
                    'productionCount' => count($registry->getProductionEntries($id)),
                    'testCount'       => count($registry->getTestEntries($id)),
                    'isOrphan'        => !$registry->hasProductionEntries($id),
                ];
            })
            ->toMatchArray([
                'productionCount' => 0,
                'testCount'       => 1,
                'isOrphan'        => true,
            ]);
    });

    describe('JSON output format', function (): void {
        it('formats placeholder data correctly for JSON output')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'Class1', 'method1', '/path', 1);
                $registry->registerProductionPlaceholder('@A', 'Class1', 'method2', '/path', 2);
                $registry->registerTestPlaceholder('@A', 'Test::test1', '/path', 1, 'pest');
                $registry->registerTestPlaceholder('@A', 'Test::test2', '/path', 2, 'pest');
                $registry->registerTestPlaceholder('@A', 'Test::test3', '/path', 3, 'phpunit');

                $placeholderIds = $registry->getAllPlaceholderIds();

                // Format as JSON output would
                $unresolvedPlaceholders = array_map(fn (string $id): array => [
                    'id'              => $id,
                    'productionCount' => count($registry->getProductionEntries($id)),
                    'testCount'       => count($registry->getTestEntries($id)),
                ], $placeholderIds);

                return $unresolvedPlaceholders;
            })
            ->toBe([
                [
                    'id'              => '@A',
                    'productionCount' => 2,
                    'testCount'       => 3,
                ],
            ]);

        it('formats multiple placeholders correctly')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'Class', 'methodA', '/path', 1);
                $registry->registerProductionPlaceholder('@B', 'Class', 'methodB', '/path', 2);
                $registry->registerTestPlaceholder('@A', 'Test::testA', '/path', 1, 'pest');

                $placeholderIds = $registry->getAllPlaceholderIds();

                $unresolvedPlaceholders = array_map(fn (string $id): array => [
                    'id'              => $id,
                    'productionCount' => count($registry->getProductionEntries($id)),
                    'testCount'       => count($registry->getTestEntries($id)),
                ], $placeholderIds);

                return count($unresolvedPlaceholders);
            })
            ->toBe(2);
    });

    describe('Pest test fixture detection', function (): void {
        it('scans Pest fixture file for placeholder linksAndCovers')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $scanner  = new PlaceholderScanner();

                $fixturePath = dirname(__DIR__, 3).'/Fixtures/Placeholder/Tests/PlaceholderPestFixture.php';

                if (!file_exists($fixturePath)) {
                    return ['exists' => false];
                }

                // Use reflection to call the private scanPestFile method
                $reflection = new ReflectionClass($scanner);
                $method     = $reflection->getMethod('scanPestFile');
                $method->setAccessible(true);
                $method->invoke($scanner, $fixturePath, $registry);

                $placeholderIds = $registry->getAllPlaceholderIds();

                return [
                    'exists'          => true,
                    'has_user_create' => in_array('@user-create', $placeholderIds, true),
                    'has_integration' => in_array('@integration', $placeholderIds, true),
                    'has_nested'      => in_array('@nested', $placeholderIds, true),
                ];
            })
            ->toMatchArray([
                'exists'          => true,
                'has_user_create' => true,
                'has_integration' => true,
                'has_nested'      => true,
            ]);
    });

    describe('PHPUnit test fixture detection', function (): void {
        it('scans PHPUnit fixture class for placeholder attributes')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $scanner  = new PlaceholderScanner();

                // Use reflection to call the private scanPhpUnitClass method
                $reflection = new ReflectionClass($scanner);
                $method     = $reflection->getMethod('scanPhpUnitClass');
                $method->setAccessible(true);

                // Load the fixture class first
                $fixturePath = dirname(__DIR__, 3).'/Fixtures/Placeholder/Tests/PlaceholderPhpUnitFixture.php';
                require_once $fixturePath;

                $className = 'Tests\\Fixtures\\Placeholder\\Tests\\PlaceholderPhpUnitTest';

                $method->invoke($scanner, $className, $registry);

                $placeholderIds = $registry->getAllPlaceholderIds();

                return [
                    'has_phpunit_test'  => in_array('@phpunit-test', $placeholderIds, true),
                    'has_phpunit_links' => in_array('@phpunit-links', $placeholderIds, true),
                    'has_X'             => in_array('@X', $placeholderIds, true),
                    'has_Y'             => in_array('@Y', $placeholderIds, true),
                ];
            })
            ->toMatchArray([
                'has_phpunit_test'  => true,
                'has_phpunit_links' => true,
                'has_X'             => true,
                'has_Y'             => true,
            ]);
    });
});
