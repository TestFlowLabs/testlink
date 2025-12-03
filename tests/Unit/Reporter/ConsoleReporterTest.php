<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Reporter\ConsoleReporter;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * Helper to capture console output using memory stream.
 *
 * @return array{reporter: ConsoleReporter, stream: resource}
 */
function createReporterWithStream(): array
{
    $stream = fopen('php://memory', 'r+');

    return [
        'reporter' => new ConsoleReporter($stream),
        'stream'   => $stream,
    ];
}

/**
 * Get output from memory stream.
 *
 * @param  resource  $stream
 */
function getStreamOutput($stream): string
{
    rewind($stream);

    return stream_get_contents($stream);
}

describe('ConsoleReporter', function (): void {
    describe('report', function (): void {
        it('outputs coverage links', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test', 'ProductionClass::method');

            ['reporter' => $reporter, 'stream' => $stream] = createReporterWithStream();

            $reporter->report($registry);
            $output = getStreamOutput($stream);

            expect($output)->toContain('Coverage Links Report')
                ->toContain('ProductionClass::method')
                ->toContain('TestClass::test')
                ->toContain('Total:')
                ->toContain('1 links')
                ->toContain('1 methods');
        });

        it('outputs message when no links', function (): void {
            $registry = new TestLinkRegistry();

            ['reporter' => $reporter, 'stream' => $stream] = createReporterWithStream();

            $reporter->report($registry);
            $output = getStreamOutput($stream);

            expect($output)->toContain('No coverage links found');
        });

        it('uses tree formatting for multiple tests', function (): void {
            $registry = new TestLinkRegistry();
            $registry->registerLink('TestClass::test1', 'ProductionClass::method');
            $registry->registerLink('TestClass::test2', 'ProductionClass::method');

            ['reporter' => $reporter, 'stream' => $stream] = createReporterWithStream();

            $reporter->report($registry);
            $output = getStreamOutput($stream);

            expect($output)->toContain('├─')
                ->toContain('└─');
        });
    });

    describe('reportValidation', function (): void {
        it('outputs success when valid', function (): void {
            $result = [
                'valid'          => true,
                'attributeLinks' => [],
                'runtimeLinks'   => [],
                'duplicates'     => [],
                'totalLinks'     => 0,
            ];

            ['reporter' => $reporter, 'stream' => $stream] = createReporterWithStream();

            $reporter->reportValidation($result);
            $output = getStreamOutput($stream);

            expect($output)->toContain('All coverage links are valid');
        });

        it('outputs duplicate links warning', function (): void {
            $result = [
                'valid'          => false,
                'attributeLinks' => ['TestClass::test' => ['ProductionClass::method']],
                'runtimeLinks'   => ['TestClass::test' => ['ProductionClass::method']],
                'duplicates'     => [
                    [
                        'test'   => 'TestClass::test',
                        'method' => 'ProductionClass::method',
                    ],
                ],
                'totalLinks' => 2,
            ];

            ['reporter' => $reporter, 'stream' => $stream] = createReporterWithStream();

            $reporter->reportValidation($result);
            $output = getStreamOutput($stream);

            expect($output)->toContain('Duplicate links found')
                ->toContain('TestClass::test')
                ->toContain('ProductionClass::method')
                ->toContain('issue');
        });

        it('counts total issues', function (): void {
            $result = [
                'valid'          => false,
                'attributeLinks' => [],
                'runtimeLinks'   => [],
                'duplicates'     => [
                    ['test' => 'T1', 'method' => 'M1'],
                    ['test' => 'T2', 'method' => 'M2'],
                    ['test' => 'T3', 'method' => 'M3'],
                ],
                'totalLinks' => 6,
            ];

            ['reporter' => $reporter, 'stream' => $stream] = createReporterWithStream();

            $reporter->reportValidation($result);
            $output = getStreamOutput($stream);

            expect($output)->toContain('3 issue');
        });

        it('shows total links when valid', function (): void {
            $result = [
                'valid'          => true,
                'attributeLinks' => ['TestClass::test' => ['ProductionClass::method']],
                'runtimeLinks'   => [],
                'duplicates'     => [],
                'totalLinks'     => 5,
            ];

            ['reporter' => $reporter, 'stream' => $stream] = createReporterWithStream();

            $reporter->reportValidation($result);
            $output = getStreamOutput($stream);

            expect($output)->toContain('Total links: 5');
        });
    });
});
