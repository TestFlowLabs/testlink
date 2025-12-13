<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Exception;

use RuntimeException;

/**
 * Exception thrown when a production file cannot be found.
 */
final class ProductionFileNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $methodIdentifier,
        public readonly string $className,
    ) {
        parent::__construct(
            "Could not find production file for '{$methodIdentifier}' (class: {$className})"
        );
    }
}
