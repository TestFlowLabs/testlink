<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Exception\ProductionFileNotFoundException;

describe('ProductionFileNotFoundException', function (): void {
    it('creates exception with method identifier', function (): void {
        $exception = new ProductionFileNotFoundException(
            'App\\Services\\UserService::create',
            'App\\Services\\UserService'
        );

        expect($exception)->toBeInstanceOf(RuntimeException::class);
        expect($exception->getMessage())->toContain('App\\Services\\UserService::create');
    });

    it('includes class name in message', function (): void {
        $exception = new ProductionFileNotFoundException(
            'App\\Domain\\Payment\\PaymentService::process',
            'App\\Domain\\Payment\\PaymentService'
        );

        expect($exception->getMessage())->toContain('PaymentService');
    });

    it('exposes method identifier property', function (): void {
        $exception = new ProductionFileNotFoundException(
            'App\\User::save',
            'App\\User'
        );

        expect($exception->methodIdentifier)->toBe('App\\User::save');
    });

    it('exposes class name property', function (): void {
        $exception = new ProductionFileNotFoundException(
            'App\\User::save',
            'App\\User'
        );

        expect($exception->className)->toBe('App\\User');
    });
});
