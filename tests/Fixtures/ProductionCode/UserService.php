<?php

declare(strict_types=1);

namespace Tests\Fixtures\ProductionCode;

use TestFlowLabs\TestLink\Attribute\TestedBy;

/**
 * Example production code with bidirectional links.
 *
 * TestedBy attributes here link to tests in Tests\Fixtures\TestCode\UserServiceTest
 */
class UserService
{
    #[TestedBy('Tests\Fixtures\TestCode\UserServiceTest', 'test_creates_user')]
    public function create(string $name, string $email): array
    {
        return [
            'name'  => $name,
            'email' => $email,
        ];
    }

    #[TestedBy('Tests\Fixtures\TestCode\UserServiceTest', 'test_updates_and_validates_user')]
    public function update(int $id, array $data): array
    {
        return array_merge(['id' => $id], $data);
    }

    #[TestedBy('Tests\Fixtures\TestCode\UserServiceTest', 'test_deletes_user_integration')]
    public function delete(int $id): bool
    {
        return true;
    }

    public function findById(int $id): ?array
    {
        return null;
    }
}
