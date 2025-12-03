<?php

declare(strict_types=1);

/**
 * Fixture: Pest test with describe blocks.
 */
describe('UserService', function (): void {
    test('it creates a user')
        ->expect(true)
        ->toBeTrue();

    test('it updates a user')
        ->expect(true)
        ->toBeTrue();

    describe('validation', function (): void {
        test('it validates email')
            ->expect(true)
            ->toBeTrue();

        test('it validates password')
            ->expect(true)
            ->toBeTrue();
    });
});

describe('OrderService', function (): void {
    it('creates an order')
        ->expect(true)
        ->toBeTrue();
});
