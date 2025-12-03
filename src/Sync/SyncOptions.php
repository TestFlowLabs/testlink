<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync;

use InvalidArgumentException;

/**
 * Value object for sync command CLI options.
 */
final readonly class SyncOptions
{
    public function __construct(
        public bool $dryRun = false,
        public bool $linkOnly = false,
        public bool $prune = false,
        public bool $force = false,
        public ?string $path = null,
    ) {
        $this->validate();
    }

    /**
     * Create options from CLI arguments.
     *
     * @param  array<int, string>  $arguments
     */
    public static function fromArguments(array $arguments): self
    {
        $dryRun   = self::hasArgument('--dry-run', $arguments);
        $linkOnly = self::hasArgument('--link-only', $arguments);
        $prune    = self::hasArgument('--prune', $arguments);
        $force    = self::hasArgument('--force', $arguments);
        $path     = self::getArgumentValue('--path', $arguments);

        return new self(
            dryRun: $dryRun,
            linkOnly: $linkOnly,
            prune: $prune,
            force: $force,
            path: $path,
        );
    }

    /**
     * Validate options consistency.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if ($this->prune && !$this->force) {
            throw new InvalidArgumentException(
                'The --prune option requires --force to confirm deletion of orphaned linksAndCovers()/links() calls.'
            );
        }
    }

    /**
     * Check if an argument exists.
     *
     * @param  array<int, string>  $arguments
     */
    private static function hasArgument(string $name, array $arguments): bool
    {
        return in_array($name, $arguments, true);
    }

    /**
     * Get argument value for --name=value format.
     *
     * @param  array<int, string>  $arguments
     */
    private static function getArgumentValue(string $name, array $arguments): ?string
    {
        foreach ($arguments as $argument) {
            if (str_starts_with($argument, $name.'=')) {
                return substr($argument, strlen($name) + 1);
            }
        }

        return null;
    }
}
