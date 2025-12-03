<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Exception\TestFileNotFoundException;

describe('TestFileNotFoundException', function (): void {
    it('creates exception with test identifier', function (): void {
        $exception = new TestFileNotFoundException(
            'Tests\\Unit\\UserTest::test create',
            'Tests\\Unit\\UserTest'
        );

        expect($exception)->toBeInstanceOf(RuntimeException::class);
        expect($exception->getMessage())->toContain('Tests\\Unit\\UserTest::test create');
    });

    it('includes class name in message', function (): void {
        $exception = new TestFileNotFoundException(
            'Tests\\Services\\PaymentTest::test process',
            'Tests\\Services\\PaymentTest'
        );

        expect($exception->getMessage())->toContain('PaymentTest');
    });
});
