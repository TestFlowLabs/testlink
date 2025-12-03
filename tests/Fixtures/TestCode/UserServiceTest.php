<?php

declare(strict_types=1);

namespace Tests\Fixtures\TestCode;

use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\Links;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

/**
 * Example test class with #[LinksAndCovers] and #[Links] attributes.
 */
class UserServiceTest extends TestCase
{
    #[LinksAndCovers('App\Services\UserService', 'create')]
    public function test_creates_user(): void
    {
        $this->assertTrue(true);
    }

    #[LinksAndCovers('App\Services\UserService', 'update')]
    #[LinksAndCovers('App\Services\UserService', 'validate')]
    public function test_updates_and_validates_user(): void
    {
        $this->assertTrue(true);
    }

    #[Links('App\Services\UserService', 'delete')]
    public function test_deletes_user_integration(): void
    {
        $this->assertTrue(true);
    }

    #[LinksAndCovers('App\Services\UserService')]
    public function test_user_service_class(): void
    {
        $this->assertTrue(true);
    }

    public function test_without_attributes(): void
    {
        // No coverage link attributes
        $this->assertTrue(true);
    }
}
