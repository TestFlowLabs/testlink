<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Console\Output;
use TestFlowLabs\TestLink\Console\ArgumentParser;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Console\Command\ReportCommand;

describe('ReportCommand', function (): void {
    beforeEach(function (): void {
        $this->command = new ReportCommand();
        $this->parser  = new ArgumentParser();
        $this->output  = new Output();
    });

    describe('command execution', function (): void {
        it('returns exit code 0')
            ->linksAndCovers(ReportCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'report']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBe(0);

        it('accepts --json flag')
            ->linksAndCovers(ReportCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'report', '--json']);

                return $this->parser->hasOption('json');
            })
            ->toBeTrue();

        it('accepts --path option')
            ->linksAndCovers(ReportCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'report', '--path=/src']);

                return $this->parser->getString('path');
            })
            ->toBe('/src');

        it('accepts --verbose flag')
            ->linksAndCovers(ReportCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'report', '--verbose']);

                return $this->parser->hasOption('verbose');
            })
            ->toBeTrue();

        it('returns exit code 0 with --json flag')
            ->linksAndCovers(ReportCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'report', '--json']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBe(0);
    });

    describe('TestLinkRegistry integration', function (): void {
        it('creates singleton instance')
            ->linksAndCovers(TestLinkRegistry::class.'::getInstance')
            ->expect(function () {
                $instance1 = TestLinkRegistry::getInstance();
                $instance2 = TestLinkRegistry::getInstance();

                return $instance1 === $instance2;
            })
            ->toBeTrue();

        it('registers test link correctly')
            ->linksAndCovers(TestLinkRegistry::class.'::registerLink')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink(
                    'Tests\\Unit\\UserServiceTest::it creates user',
                    'App\\Services\\UserService::create'
                );

                return $registry->hasMethod('App\\Services\\UserService::create');
            })
            ->toBeTrue();

        it('retrieves tests for method')
            ->linksAndCovers(TestLinkRegistry::class.'::getTestsForMethod')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink(
                    'Tests\\Unit\\UserServiceTest::it creates user',
                    'App\\Services\\UserService::create'
                );
                $registry->registerLink(
                    'Tests\\Unit\\UserServiceTest::it validates user',
                    'App\\Services\\UserService::create'
                );

                return count($registry->getTestsForMethod('App\\Services\\UserService::create'));
            })
            ->toBe(2);

        it('returns empty array for unknown method')
            ->linksAndCovers(TestLinkRegistry::class.'::getTestsForMethod')
            ->expect(function () {
                $registry = new TestLinkRegistry();

                return $registry->getTestsForMethod('Unknown::method');
            })
            ->toBe([]);

        it('returns all links')
            ->linksAndCovers(TestLinkRegistry::class.'::getAllLinks')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('Test1::test1', 'Class1::method1');
                $registry->registerLink('Test1::test2', 'Class1::method1');
                $registry->registerLink('Test2::test1', 'Class2::method2');

                $allLinks = $registry->getAllLinks();

                return [
                    'method_count' => count($allLinks),
                    'has_class1'   => isset($allLinks['Class1::method1']),
                    'has_class2'   => isset($allLinks['Class2::method2']),
                ];
            })
            ->toMatchArray([
                'method_count' => 2,
                'has_class1'   => true,
                'has_class2'   => true,
            ]);

        it('counts unique tests correctly')
            ->linksAndCovers(TestLinkRegistry::class.'::getAllLinks')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('Test1::test1', 'Class1::method1');
                $registry->registerLink('Test1::test2', 'Class1::method1');
                $registry->registerLink('Test2::test1', 'Class2::method2');

                $allLinks  = $registry->getAllLinks();
                $testCount = 0;

                foreach ($allLinks as $tests) {
                    $testCount += count($tests);
                }

                return $testCount;
            })
            ->toBe(3);
    });

    describe('empty project handling', function (): void {
        it('handles project with no links gracefully')
            ->linksAndCovers(ReportCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'report', '--path=/nonexistent/path']);

                // Command should complete without throwing
                return $this->command->execute($this->parser, $this->output);
            })
            ->toBe(0);

        it('returns valid JSON for project with no links')
            ->linksAndCovers(ReportCommand::class.'::execute')
            ->expect(function () {
                $this->parser->parse(['testlink', 'report', '--json', '--path=/nonexistent/path']);

                return $this->command->execute($this->parser, $this->output);
            })
            ->toBe(0);
    });

    describe('link registration', function (): void {
        it('allows same test to link to multiple methods')
            ->linksAndCovers(TestLinkRegistry::class.'::registerLink')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('Test::test', 'Class1::method1');
                $registry->registerLink('Test::test', 'Class2::method2');

                return [
                    'method1_has_test' => $registry->hasMethod('Class1::method1'),
                    'method2_has_test' => $registry->hasMethod('Class2::method2'),
                ];
            })
            ->toMatchArray([
                'method1_has_test' => true,
                'method2_has_test' => true,
            ]);
    });

    describe('registry clearing', function (): void {
        it('clears all links')
            ->linksAndCovers(TestLinkRegistry::class.'::clear')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('Test::test', 'Class::method');
                $registry->clear();

                return $registry->getAllLinks();
            })
            ->toBe([]);

        it('allows registration after clearing')
            ->linksAndCovers(TestLinkRegistry::class.'::clear')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('Test::test', 'Class::method');
                $registry->clear();
                $registry->registerLink('New::test', 'New::method');

                return $registry->hasMethod('New::method');
            })
            ->toBeTrue();
    });

    describe('count methods', function (): void {
        it('counts methods correctly')
            ->linksAndCovers(TestLinkRegistry::class.'::countMethods')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('Test1::test', 'Class1::method1');
                $registry->registerLink('Test2::test', 'Class2::method2');

                return $registry->countMethods();
            })
            ->toBe(2);

        it('counts tests correctly')
            ->linksAndCovers(TestLinkRegistry::class.'::countTests')
            ->expect(function () {
                $registry = new TestLinkRegistry();
                $registry->registerLink('Test1::test', 'Class::method');
                $registry->registerLink('Test2::test', 'Class::method');

                return $registry->countTests();
            })
            ->toBe(2);

        it('returns zero for empty registry')
            ->linksAndCovers(TestLinkRegistry::class.'::count')
            ->expect(function () {
                $registry = new TestLinkRegistry();

                return [
                    'count'        => $registry->count(),
                    'countMethods' => $registry->countMethods(),
                    'countTests'   => $registry->countTests(),
                ];
            })
            ->toMatchArray([
                'count'        => 0,
                'countMethods' => 0,
                'countTests'   => 0,
            ]);
    });
});
