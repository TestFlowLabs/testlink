<?php

declare(strict_types=1);

/**
 * Fixture: Pest test with chained link methods.
 */
test('it creates a user')
    ->linksAndCovers(App\Services\UserService::class.'::create')
    ->expect(true)
    ->toBeTrue();

test('it validates user data')
    ->links(App\Services\UserService::class.'::validate')
    ->expect(true)
    ->toBeTrue();

test('it handles multiple methods')
    ->linksAndCovers(App\Services\UserService::class.'::create')
    ->linksAndCovers(App\Services\UserService::class.'::validate')
    ->expect(true)
    ->toBeTrue();

it('works with it syntax and links')
    ->linksAndCovers(App\Services\UserService::class.'::delete')
    ->expect(true)
    ->toBeTrue();
