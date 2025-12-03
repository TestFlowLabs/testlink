<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Exception;

use RuntimeException;

/**
 * Exception thrown when a test case cannot be found in a file.
 */
final class TestCaseNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $testName,
        public readonly string $filePath,
        ?string $reason = null,
    ) {
        $message = "Could not find test case '{$testName}' in file '{$filePath}'";

        if ($reason !== null) {
            $message .= ": {$reason}";
        }

        parent::__construct($message);
    }
}
