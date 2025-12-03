<?php

declare(strict_types=1);

use TestFlowLabs\TestingAttributes\Links;

describe('Links Attribute', function (): void {
    describe('constructor', function (): void {
        it('creates method identifier with class and method')
            ->expect(fn () => new Links('App\\Services\\UserService', 'create'))
            ->methodIdentifier->toBe('App\\Services\\UserService::create');

        it('creates class-level identifier when method is null')
            ->expect(fn () => new Links('App\\Services\\UserService'))
            ->methodIdentifier->toBe('App\\Services\\UserService');

        it('stores class property')
            ->expect(fn () => new Links('App\\Services\\UserService', 'create'))
            ->class->toBe('App\\Services\\UserService');

        it('stores method property')
            ->expect(fn () => new Links('App\\Services\\UserService', 'create'))
            ->method->toBe('create');

        it('stores null method for class-level link')
            ->expect(fn () => new Links('App\\Services\\UserService'))
            ->method->toBeNull();
    });

    describe('isClassLevel', function (): void {
        it('returns false when method is specified')
            ->expect(fn () => (new Links('App\\Services\\UserService', 'create'))->isClassLevel())
            ->toBeFalse();

        it('returns true when method is null')
            ->expect(fn () => (new Links('App\\Services\\UserService'))->isClassLevel())
            ->toBeTrue();
    });

    describe('getMethodIdentifier', function (): void {
        it('returns formatted method identifier')
            ->expect(fn () => (new Links('App\\Services\\UserService', 'create'))->getMethodIdentifier())
            ->toBe('App\\Services\\UserService::create');

        it('returns class-only identifier when no method')
            ->expect(fn () => (new Links('App\\Services\\UserService'))->getMethodIdentifier())
            ->toBe('App\\Services\\UserService');
    });
});
