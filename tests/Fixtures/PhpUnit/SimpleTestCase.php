<?php

declare(strict_types=1);

namespace Tests\Fixtures\PhpUnit;

use PHPUnit\Framework\TestCase;

/**
 * Fixture: Simple PHPUnit test case without any link attributes.
 */
class SimpleTestCase extends TestCase
{
    public function test_it_does_something(): void
    {
        $this->assertTrue(true);
    }

    public function test_it_does_another_thing(): void
    {
        $this->assertEquals(2, 1 + 1);
    }

    public function test_with_snake_case(): void
    {
        $this->assertSame(42, 42);
    }
}
