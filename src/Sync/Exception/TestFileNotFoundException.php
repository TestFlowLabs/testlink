<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Exception;

use RuntimeException;

/**
 * Exception thrown when a test file cannot be found.
 */
final class TestFileNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $testIdentifier,
        public readonly string $className,
    ) {
        parent::__construct(
            "Could not find test file for '{$testIdentifier}' (class: {$className})"
        );
    }
}
