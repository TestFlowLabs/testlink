<?php

declare(strict_types=1);

use TestFlowLabs\TestingAttributes\TestedBy;

describe('TestedBy Attribute', function (): void {
    it('can be instantiated with class and method', function (): void {
        $attribute = new TestedBy('Tests\Unit\UserServiceTest', 'test_creates_user');

        expect($attribute->testClass)->toBe('Tests\Unit\UserServiceTest');
        expect($attribute->testMethod)->toBe('test_creates_user');
    });

    it('can be instantiated with class only', function (): void {
        $attribute = new TestedBy('Tests\Unit\UserServiceTest');

        expect($attribute->testClass)->toBe('Tests\Unit\UserServiceTest');
        expect($attribute->testMethod)->toBeNull();
    });

    it('returns test identifier with method', function (): void {
        $attribute = new TestedBy('Tests\Unit\UserServiceTest', 'test_creates_user');

        expect($attribute->getTestIdentifier())->toBe('Tests\Unit\UserServiceTest::test_creates_user');
    });

    it('returns test identifier without method', function (): void {
        $attribute = new TestedBy('Tests\Unit\UserServiceTest');

        expect($attribute->getTestIdentifier())->toBe('Tests\Unit\UserServiceTest');
    });

    it('is a valid PHP attribute', function (): void {
        $reflection = new ReflectionClass(TestedBy::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        expect($attributes)->toHaveCount(1);
    });

    it('targets methods and is repeatable', function (): void {
        $reflection = new ReflectionClass(TestedBy::class);
        $attributes = $reflection->getAttributes(\Attribute::class);
        $instance   = $attributes[0]->newInstance();

        expect($instance->flags & \Attribute::TARGET_METHOD)->toBe(\Attribute::TARGET_METHOD);
        expect($instance->flags & \Attribute::IS_REPEATABLE)->toBe(\Attribute::IS_REPEATABLE);
    });
});
