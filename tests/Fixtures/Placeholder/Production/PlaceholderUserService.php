<?php

declare(strict_types=1);

namespace Tests\Fixtures\Placeholder\Production;

use TestFlowLabs\TestingAttributes\TestedBy;

/**
 * Fixture production class with placeholder TestedBy attributes.
 */
class PlaceholderUserService
{
    #[TestedBy('@user-create')]
    public function create(string $name, string $email): array
    {
        return [
            'name'  => $name,
            'email' => $email,
        ];
    }

    #[TestedBy('@A')]
    #[TestedBy('@B')]
    public function multiTested(): void
    {
        // Method with multiple placeholder attributes
    }

    #[TestedBy('Tests\Fixtures\TestCode\UserServiceTest', 'test_creates_user')]
    public function realTestedBy(): void
    {
        // This should NOT be picked up as placeholder
    }
}
