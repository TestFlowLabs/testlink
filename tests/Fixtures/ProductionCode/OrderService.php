<?php

declare(strict_types=1);

namespace Tests\Fixtures\ProductionCode;

use TestFlowLabs\TestingAttributes\TestedBy;

/**
 * Example production code with TestedBy attributes for bidirectional linking tests.
 */
class OrderService
{
    #[TestedBy('Tests\Fixtures\TestCode\OrderServiceTest', 'testCreatesOrder')]
    public function create(array $items): array
    {
        return [
            'id'    => 1,
            'items' => $items,
        ];
    }

    #[TestedBy('Tests\Fixtures\TestCode\OrderServiceTest', 'testUpdatesOrder')]
    #[TestedBy('Tests\Fixtures\TestCode\OrderServiceTest', 'testValidatesOrder')]
    public function update(int $id, array $data): array
    {
        return array_merge(['id' => $id], $data);
    }

    #[TestedBy('Tests\Fixtures\TestCode\OrderServiceTest', 'testCancelsOrder')]
    public function cancel(int $id): bool
    {
        return true;
    }

    // Method without TestedBy - should trigger "missing TestedBy" validation error
    // if tests link to it
    public function findById(int $id): ?array
    {
        return null;
    }
}
