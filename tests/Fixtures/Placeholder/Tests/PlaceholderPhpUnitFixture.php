<?php

declare(strict_types=1);

namespace Tests\Fixtures\Placeholder\Tests;

use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\Links;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

/**
 * Fixture PHPUnit test class with placeholder attributes.
 */
class PlaceholderPhpUnitTest extends TestCase
{
    #[LinksAndCovers('@phpunit-test')]
    public function test_creates_user(): void
    {
        $this->assertTrue(true);
    }

    #[Links('@phpunit-links')]
    public function test_integration(): void
    {
        $this->assertTrue(true);
    }

    #[LinksAndCovers('@X')]
    #[LinksAndCovers('@Y')]
    public function test_multiple(): void
    {
        $this->assertTrue(true);
    }

    #[LinksAndCovers(\Tests\Fixtures\ProductionCode\UserService::class, 'create')]
    public function test_real_links(): void
    {
        // This should NOT be picked up as placeholder
        $this->assertTrue(true);
    }
}
