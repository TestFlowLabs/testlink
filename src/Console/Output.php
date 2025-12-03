<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console;

/**
 * ANSI colored output helper for CLI.
 *
 * Provides colored console output without external dependencies.
 */
final class Output
{
    // ANSI color codes
    private const RESET = "\033[0m";

    private const BOLD = "\033[1m";

    private const DIM = "\033[2m";

    private const RED = "\033[31m";

    private const GREEN = "\033[32m";

    private const YELLOW = "\033[33m";

    private const BLUE = "\033[34m";

    private const MAGENTA = "\033[35m";

    private const CYAN = "\033[36m";

    private const WHITE = "\033[37m";

    private const GRAY = "\033[90m";

    private readonly bool $supportsColor;

    /** @var resource */
    private $stdout = STDOUT;

    /** @var resource */
    private $stderr = STDERR;

    public function __construct()
    {
        $this->supportsColor = $this->detectColorSupport();
    }

    /**
     * Write a line to stdout.
     */
    public function writeln(string $message = ''): self
    {
        fwrite($this->stdout, $message.PHP_EOL);

        return $this;
    }

    /**
     * Write to stdout without newline.
     */
    public function write(string $message): self
    {
        fwrite($this->stdout, $message);

        return $this;
    }

    /**
     * Write an error line to stderr.
     */
    public function error(string $message): self
    {
        fwrite($this->stderr, $this->red($message).PHP_EOL);

        return $this;
    }

    /**
     * Write a success message.
     */
    public function success(string $message): self
    {
        return $this->writeln($this->green('  '.$message));
    }

    /**
     * Write a warning message.
     */
    public function warning(string $message): self
    {
        return $this->writeln($this->yellow('  '.$message));
    }

    /**
     * Write an info message.
     */
    public function info(string $message): self
    {
        return $this->writeln($this->cyan('  '.$message));
    }

    /**
     * Write a comment (gray text).
     */
    public function comment(string $message): self
    {
        return $this->writeln($this->gray('  '.$message));
    }

    /**
     * Write a title.
     */
    public function title(string $title): self
    {
        $this->writeln();
        $this->writeln($this->bold($this->cyan('  '.$title)));
        $this->writeln($this->gray('  '.str_repeat('─', strlen($title))));

        return $this;
    }

    /**
     * Write a section header.
     */
    public function section(string $title): self
    {
        $this->writeln();
        $this->writeln($this->bold('  '.$title));

        return $this;
    }

    /**
     * Write a list item.
     */
    public function listItem(string $item, string $prefix = '•'): self
    {
        return $this->writeln("    {$prefix} {$item}");
    }

    /**
     * Write a table.
     *
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public function table(array $headers, array $rows): self
    {
        if ($rows === []) {
            return $this;
        }

        // Calculate column widths
        $widths = array_map(strlen(...), $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, strlen($cell));
            }
        }

        // Print headers
        $this->writeln();
        $headerLine = '  ';

        foreach ($headers as $i => $header) {
            $headerLine .= str_pad($header, $widths[$i] + 2);
        }

        $this->writeln($this->bold($headerLine));
        $this->writeln($this->gray('  '.str_repeat('─', array_sum($widths) + (count($widths) * 2))));

        // Print rows
        foreach ($rows as $row) {
            $line = '  ';

            foreach ($row as $i => $cell) {
                $line .= str_pad($cell, ($widths[$i] ?? strlen($cell)) + 2);
            }

            $this->writeln($line);
        }

        return $this;
    }

    /**
     * Write a newline.
     */
    public function newLine(int $count = 1): self
    {
        for ($i = 0; $i < $count; $i++) {
            $this->writeln();
        }

        return $this;
    }

    // Color methods

    public function red(string $text): string
    {
        return $this->colorize(self::RED, $text);
    }

    public function green(string $text): string
    {
        return $this->colorize(self::GREEN, $text);
    }

    public function yellow(string $text): string
    {
        return $this->colorize(self::YELLOW, $text);
    }

    public function blue(string $text): string
    {
        return $this->colorize(self::BLUE, $text);
    }

    public function magenta(string $text): string
    {
        return $this->colorize(self::MAGENTA, $text);
    }

    public function cyan(string $text): string
    {
        return $this->colorize(self::CYAN, $text);
    }

    public function white(string $text): string
    {
        return $this->colorize(self::WHITE, $text);
    }

    public function gray(string $text): string
    {
        return $this->colorize(self::GRAY, $text);
    }

    public function bold(string $text): string
    {
        return $this->colorize(self::BOLD, $text);
    }

    public function dim(string $text): string
    {
        return $this->colorize(self::DIM, $text);
    }

    /**
     * Apply color code to text.
     */
    private function colorize(string $code, string $text): string
    {
        if (!$this->supportsColor) {
            return $text;
        }

        return $code.$text.self::RESET;
    }

    /**
     * Detect if terminal supports colors.
     */
    private function detectColorSupport(): bool
    {
        // Check for NO_COLOR environment variable
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        // Check for FORCE_COLOR
        if (getenv('FORCE_COLOR') !== false) {
            return true;
        }

        // Check if stdout is a TTY
        if (function_exists('posix_isatty') && !posix_isatty($this->stdout)) {
            return false;
        }

        // Check TERM environment variable
        $term = getenv('TERM');

        if ($term === false) {
            return false;
        }

        return $term !== 'dumb';
    }

    /**
     * Check if colors are supported.
     */
    public function supportsColor(): bool
    {
        return $this->supportsColor;
    }
}
