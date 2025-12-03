<?php

declare(strict_types=1);

// Fixture Pest test file with placeholder linksAndCovers/links calls.

test('creates user', function (): void {
    expect(true)->toBeTrue();
})->linksAndCovers('@user-create');

test('integration test')->links('@integration');

describe('UserService', function (): void {
    describe('create', function (): void {
        test('creates user in nested block', function (): void {
            expect(true)->toBeTrue();
        })->linksAndCovers('@nested');
    });
});

test('multi method test', function (): void {
    // test
})->linksAndCovers('@multi-A')->linksAndCovers('@multi-B');

// This should NOT be picked up as placeholder
test('real links test', function (): void {
    // test
})->linksAndCovers(\Tests\Fixtures\ProductionCode\UserService::class.'::create');
