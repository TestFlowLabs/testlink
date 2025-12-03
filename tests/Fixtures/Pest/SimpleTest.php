<?php

declare(strict_types=1);

/**
 * Fixture: Simple Pest test without any link methods.
 */
test('it does something')
    ->expect(true)
    ->toBeTrue();

test('it does another thing', function (): void {
    expect(1 + 1)->toBe(2);
});

it('works with it syntax')
    ->expect(42)
    ->toBe(42);
