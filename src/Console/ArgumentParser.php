<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console;

/**
 * Native PHP argument parser for CLI commands.
 *
 * Parses command line arguments without external dependencies.
 */
final class ArgumentParser
{
    /** @var array<string, string|bool> */
    private array $options = [];

    /** @var list<string> */
    private array $arguments = [];

    private ?string $command = null;

    /**
     * Parse command line arguments.
     *
     * @param  list<string>  $argv
     */
    public function parse(array $argv): self
    {
        // Remove script name
        array_shift($argv);

        foreach ($argv as $arg) {
            if ($this->isLongOption($arg)) {
                $this->parseLongOption($arg);
            } elseif ($this->isShortOption($arg)) {
                $this->parseShortOption($arg);
            } elseif ($this->command === null) {
                $this->command = $arg;
            } else {
                $this->arguments[] = $arg;
            }
        }

        return $this;
    }

    /**
     * Get the command name.
     */
    public function getCommand(): ?string
    {
        return $this->command;
    }

    /**
     * Get positional arguments.
     *
     * @return list<string>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get an option value.
     */
    public function getOption(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Check if an option exists.
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Get all options.
     *
     * @return array<string, string|bool>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Check if argument is a long option (--name or --name=value).
     */
    private function isLongOption(string $arg): bool
    {
        return str_starts_with($arg, '--');
    }

    /**
     * Check if argument is a short option (-n or -n value).
     */
    private function isShortOption(string $arg): bool
    {
        return str_starts_with($arg, '-') && !str_starts_with($arg, '--');
    }

    /**
     * Parse a long option.
     */
    private function parseLongOption(string $arg): void
    {
        $arg = substr($arg, 2); // Remove --

        if (str_contains($arg, '=')) {
            [$name, $value]       = explode('=', $arg, 2);
            $this->options[$name] = $value;
        } else {
            $this->options[$arg] = true;
        }
    }

    /**
     * Parse a short option.
     */
    private function parseShortOption(string $arg): void
    {
        $arg = substr($arg, 1); // Remove -

        // Handle combined short options like -abc
        if (strlen($arg) > 1 && !str_contains($arg, '=')) {
            foreach (str_split($arg) as $char) {
                $this->options[$char] = true;
            }

            return;
        }

        if (str_contains($arg, '=')) {
            [$name, $value]       = explode('=', $arg, 2);
            $this->options[$name] = $value;
        } else {
            $this->options[$arg] = true;
        }
    }

    /**
     * Get boolean option value.
     */
    public function getBool(string $name): bool
    {
        $value = $this->options[$name] ?? false;

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower($value), ['true', '1', 'yes'], true);
    }

    /**
     * Get string option value.
     */
    public function getString(string $name, ?string $default = null): ?string
    {
        $value = $this->options[$name] ?? null;

        if ($value === null || $value === true) {
            return $default;
        }

        return (string) $value;
    }
}
