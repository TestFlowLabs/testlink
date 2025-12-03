<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Reporter\JsonReporter;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

describe('JsonReporter', function (): void {
    describe('report', function (): void {
        it('outputs valid JSON', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test', 'ProductionClass::method');

            $reporter = new JsonReporter();
            $output   = $reporter->report($registry);

            expect(json_decode($output, true))->not->toBeNull();
        });

        it('includes links grouped by method', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method');

            $reporter = new JsonReporter();
            $data     = json_decode($reporter->report($registry), true);

            expect($data['links'])->toBe([
                'ProductionClass::method' => ['TestClass::test1', 'TestClass::test2'],
            ]);
        });

        it('includes summary', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method1');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method2');

            $reporter = new JsonReporter();
            $data     = json_decode($reporter->report($registry), true);

            expect($data['summary'])->toBe([
                'totalLinks'     => 2,
                'methodsCovered' => 2,
                'testsWithLinks' => 2,
            ]);
        });

        it('handles empty registry', function (): void {
            $registry = new TestLinkRegistry();

            $reporter = new JsonReporter();
            $data     = json_decode($reporter->report($registry), true);

            expect($data['links'])->toBe([]);
            expect($data['summary']['totalLinks'])->toBe(0);
        });
    });

    describe('reportValidation', function (): void {
        it('outputs valid JSON', function (): void {
            $result = [
                'valid'               => true,
                'missingInTests'      => [],
                'missingInAttributes' => [],
            ];

            $reporter = new JsonReporter();
            $output   = $reporter->reportValidation($result);

            expect(json_decode($output, true))->not->toBeNull();
        });

        it('includes valid flag', function (): void {
            $result = [
                'valid'               => true,
                'missingInTests'      => [],
                'missingInAttributes' => [],
            ];

            $reporter = new JsonReporter();
            $data     = json_decode($reporter->reportValidation($result), true);

            expect($data['valid'])->toBeTrue();
        });

        it('includes issues when invalid', function (): void {
            $result = [
                'valid'          => false,
                'missingInTests' => [
                    ['method' => 'ProductionClass::method', 'expectedTests' => ['TestClass::test']],
                ],
                'missingInAttributes' => [
                    ['test' => 'TestClass::test2', 'expectedMethods' => ['ProductionClass::method2']],
                ],
            ];

            $reporter = new JsonReporter();
            $data     = json_decode($reporter->reportValidation($result), true);

            expect($data['valid'])->toBeFalse();
            expect($data['issues']['missingInTests'])->toHaveCount(1);
            expect($data['issues']['missingInAttributes'])->toHaveCount(1);
        });

        it('includes summary counts', function (): void {
            $result = [
                'valid'          => false,
                'missingInTests' => [
                    ['method' => 'M1', 'expectedTests' => ['T1']],
                    ['method' => 'M2', 'expectedTests' => ['T2']],
                ],
                'missingInAttributes' => [
                    ['test' => 'T3', 'expectedMethods' => ['M3']],
                ],
            ];

            $reporter = new JsonReporter();
            $data     = json_decode($reporter->reportValidation($result), true);

            expect($data['summary'])->toBe([
                'totalIssues'         => 3,
                'missingInTests'      => 2,
                'missingInAttributes' => 1,
            ]);
        });
    });
});
