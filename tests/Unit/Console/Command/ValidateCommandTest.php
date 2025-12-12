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

    describe('command execution', function (): void {
        beforeEach(function (): void {
            $this->command = new ValidateCommand();
            $this->parser  = new \TestFlowLabs\TestLink\Console\ArgumentParser();
            $this->output  = new Output();
        });

        it('returns exit code as int')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'validate']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBeInt();

        it('accepts --json flag')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'validate', '--json']);

                return $this->parser->hasOption('json');
            })
            ->toBeTrue();

        it('accepts --strict flag')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'validate', '--strict']);

                return $this->parser->hasOption('strict');
            })
            ->toBeTrue();

        it('accepts --path option')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'validate', '--path=/src']);

                return $this->parser->getString('path');
            })
            ->toBe('/src');

        it('returns exit code 0 with --json flag')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'validate', '--json', '--path=/nonexistent']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBe(0);
    });

    describe('empty project', function (): void {
        beforeEach(function (): void {
            $this->command = new ValidateCommand();
            $this->parser  = new \TestFlowLabs\TestLink\Console\ArgumentParser();
            $this->output  = new Output();
        });

        it('returns exit code 0 for empty project')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'validate', '--path=/nonexistent/empty/project']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBe(0);

        it('returns exit code 0 for empty project with --json')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'validate', '--json', '--path=/nonexistent/empty/project']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBe(0);
    });

    describe('@see orphan detection', function (): void {
        it('detects orphan @see tags pointing to non-existent classes')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $command = new ValidateCommand();

                // Create a SeeTagRegistry with an orphan @see tag
                $seeRegistry = new \TestFlowLabs\TestLink\DocBlock\SeeTagRegistry();

                // Add a @see tag pointing to a non-existent class
                $orphanEntry = new \TestFlowLabs\TestLink\DocBlock\SeeTagEntry(
                    reference: '\Tests\Unit\NonExistentClass::test_method',
                    filePath: '/path/to/production/Service.php',
                    line: 10,
                    context: 'production',
                    methodName: 'someMethod'
                );
                $seeRegistry->registerProductionSee('SomeClass::someMethod', $orphanEntry);

                // Use reflection to call private findSeeOrphans method
                $reflection = new ReflectionClass($command);
                $method     = $reflection->getMethod('findSeeOrphans');

                // Create empty registries (test reference doesn't exist in registries)
                $attributeRegistry = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();
                $pestRegistry      = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();

                $orphans = $method->invoke($command, $seeRegistry, $attributeRegistry, $pestRegistry);

                return count($orphans);
            })
            ->toBe(1); // Non-existent class is detected as orphan

        it('detects orphan @see tags when class exists but method does not')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $command = new ValidateCommand();

                // Create a SeeTagRegistry with an orphan @see tag
                $seeRegistry = new \TestFlowLabs\TestLink\DocBlock\SeeTagRegistry();

                // Add a @see tag pointing to an existing class but non-existent method
                $orphanEntry = new \TestFlowLabs\TestLink\DocBlock\SeeTagEntry(
                    reference: '\stdClass::nonExistentMethod',
                    filePath: '/path/to/production/Service.php',
                    line: 10,
                    context: 'production',
                    methodName: 'someMethod'
                );
                $seeRegistry->registerProductionSee('SomeClass::someMethod', $orphanEntry);

                // Use reflection to call private findSeeOrphans method
                $reflection = new ReflectionClass($command);
                $method     = $reflection->getMethod('findSeeOrphans');

                // Create empty registries (test reference doesn't exist in registries)
                $attributeRegistry = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();
                $pestRegistry      = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();

                $orphans = $method->invoke($command, $seeRegistry, $attributeRegistry, $pestRegistry);

                return count($orphans);
            })
            ->toBe(1); // stdClass exists but doesn't have nonExistentMethod

        it('targetExists returns false for non-existent classes')
            ->linksAndCovers(ValidateCommand::class.'::execute')
            ->expect(function () {
                $command = new ValidateCommand();

                // Use reflection to call private targetExists method
                $reflection = new ReflectionClass($command);
                $method     = $reflection->getMethod('targetExists');

                // Test with a class that definitely doesn't exist
                return $method->invoke($command, '\Totally\Made\Up\ClassName\That\Does\Not\Exist::someMethod');
            })
            ->toBeFalse(); // Non-existent class returns false
    });

    describe('LinkValidator integration', function (): void {
        it('creates valid validation result structure')
            ->linksAndCovers(\TestFlowLabs\TestLink\Validator\LinkValidator::class.'::validate')
            ->expect(function () {
                $validator         = new \TestFlowLabs\TestLink\Validator\LinkValidator();
                $attributeRegistry = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();
                $runtimeRegistry   = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();

                $result = $validator->validate($attributeRegistry, $runtimeRegistry);

                return [
                    'has_valid'          => array_key_exists('valid', $result),
                    'has_attributeLinks' => array_key_exists('attributeLinks', $result),
                    'has_runtimeLinks'   => array_key_exists('runtimeLinks', $result),
                    'has_duplicates'     => array_key_exists('duplicates', $result),
                    'has_totalLinks'     => array_key_exists('totalLinks', $result),
                ];
            })
            ->toMatchArray([
                'has_valid'          => true,
                'has_attributeLinks' => true,
                'has_runtimeLinks'   => true,
                'has_duplicates'     => true,
                'has_totalLinks'     => true,
            ]);

        it('returns valid true for empty registries')
            ->linksAndCovers(\TestFlowLabs\TestLink\Validator\LinkValidator::class.'::validate')
            ->expect(function () {
                $validator         = new \TestFlowLabs\TestLink\Validator\LinkValidator();
                $attributeRegistry = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();
                $runtimeRegistry   = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();

                $result = $validator->validate($attributeRegistry, $runtimeRegistry);

                return $result['valid'];
            })
            ->toBeTrue();

        it('returns zero duplicates for empty registries')
            ->linksAndCovers(\TestFlowLabs\TestLink\Validator\LinkValidator::class.'::validate')
            ->expect(function () {
                $validator         = new \TestFlowLabs\TestLink\Validator\LinkValidator();
                $attributeRegistry = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();
                $runtimeRegistry   = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();

                $result = $validator->validate($attributeRegistry, $runtimeRegistry);

                return count($result['duplicates']);
            })
            ->toBe(0);

        it('returns zero totalLinks for empty registries')
            ->linksAndCovers(\TestFlowLabs\TestLink\Validator\LinkValidator::class.'::validate')
            ->expect(function () {
                $validator         = new \TestFlowLabs\TestLink\Validator\LinkValidator();
                $attributeRegistry = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();
                $runtimeRegistry   = new \TestFlowLabs\TestLink\Registry\TestLinkRegistry();

                $result = $validator->validate($attributeRegistry, $runtimeRegistry);

                return $result['totalLinks'];
            })
            ->toBe(0);
    });
});
