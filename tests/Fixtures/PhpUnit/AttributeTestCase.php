<?php

declare(strict_types=1);

namespace Tests\Fixtures\PhpUnit;

use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TestFlowLabs\TestingAttributes\Links;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

/**
 * Fixture: PHPUnit test case with link attributes.
 */
class AttributeTestCase extends TestCase
{
    #[LinksAndCovers(UserService::class, 'create')]
    public function test_it_creates_user(): void
    {
        $this->assertTrue(true);
    }

    #[Links(UserService::class, 'validate')]
    public function test_it_validates_user(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    #[LinksAndCovers(UserService::class, 'delete')]
    public function it_deletes_user(): void
    {
        $this->assertTrue(true);
    }

    #[LinksAndCovers(UserService::class, 'create')]
    #[LinksAndCovers(UserService::class, 'notify')]
    public function test_with_multiple_attributes(): void
    {
        $this->assertTrue(true);
    }
}
