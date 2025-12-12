<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Modifier;

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;
use TestFlowLabs\TestLink\Contract\TestModifierInterface;

/**
 * Modifies PHPUnit test files to inject or remove link attributes.
 *
 * For PHPUnit tests, we inject attributes above the method declaration:
 *
 *     #[LinksAndCovers(UserService::class, 'create')]
 *     public function testCreatesUser(): void { }
 */
final class PhpUnitTestModifier implements TestModifierInterface
{
    /**
     * {@inheritDoc}
     */
    public function injectLinks(
        string $code,
        ParsedTestCase $testCase,
        array $methods,
        bool $withCoverage = true,
    ): string {
        // Filter out methods that already have the attribute
        $methodsToAdd = array_values(array_filter(
            $methods,
            fn (string $method): bool => !$testCase->hasCoversMethod($method)
        ));

        if ($methodsToAdd === []) {
            return $code;
        }

        $attributeName = $withCoverage ? 'LinksAndCovers' : 'Links';
        $lines         = explode("\n", $code);

        // Find the line where the method declaration starts
        $methodLine = $this->findMethodDeclarationLine($lines, $testCase);

        if ($methodLine === null) {
            return $code;
        }

        // Build attribute lines
        $indent         = $this->detectIndentation($lines[$methodLine]);
        $attributeLines = [];

        foreach ($methodsToAdd as $method) {
            $formatted        = $this->formatAttributeArguments($method);
            $attributeLines[] = $indent."#[{$attributeName}({$formatted})]";
        }

        // Check if we need to add use statement
        $code  = $this->ensureUseStatement($code, $attributeName);
        $lines = explode("\n", $code);

        // Recalculate method line after potential use statement addition
        $methodLine = $this->findMethodDeclarationLine($lines, $testCase);

        if ($methodLine === null) {
            return $code;
        }

        // Insert attributes before the method
        array_splice($lines, $methodLine, 0, $attributeLines);

        return implode("\n", $lines);
    }

    /**
     * {@inheritDoc}
     */
    public function removeLinks(
        string $code,
        ParsedTestCase $testCase,
        array $methods,
    ): string {
        $lines         = explode("\n", $code);
        $filteredLines = [];

        foreach ($lines as $line) {
            $shouldRemove = false;

            // Check if line contains a link attribute
            if (preg_match('/#\[(Links|LinksAndCovers)\s*\(/', $line)) {
                foreach ($methods as $method) {
                    $formatted = $this->formatAttributeArguments($method);

                    if (str_contains($line, $formatted)) {
                        $shouldRemove = true;
                        break;
                    }
                }
            }

            if (!$shouldRemove) {
                $filteredLines[] = $line;
            }
        }

        return implode("\n", $filteredLines);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(ParsedTestCase $testCase): bool
    {
        return $testCase->isPhpUnit();
    }

    /**
     * Find the line index where the method declaration starts.
     *
     * @param  list<string>  $lines
     */
    private function findMethodDeclarationLine(array $lines, ParsedTestCase $testCase): ?int
    {
        $methodName = $testCase->name;

        // Search around the start line for the method declaration
        for ($i = max(0, $testCase->startLine - 5); $i <= min(count($lines) - 1, $testCase->startLine + 5); $i++) {
            $line = $lines[$i];

            // Look for: public function testMethodName or protected function testMethodName
            if (preg_match('/^\s*(public|protected|private)?\s*function\s+'.preg_quote($methodName, '/').'\s*\(/', $line)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Detect the indentation of a line.
     */
    private function detectIndentation(string $line): string
    {
        if (preg_match('/^(\s*)/', $line, $matches)) {
            return $matches[1];
        }

        return '    '; // Default to 4 spaces
    }

    /**
     * Format method reference for attribute arguments.
     *
     * Input: "App\Services\UserService::create"
     * Output: "UserService::class, 'create'"
     *
     * Input: "App\Services\UserService"
     * Output: "UserService::class"
     */
    private function formatAttributeArguments(string $method): string
    {
        if (!str_contains($method, '::')) {
            // Class-level reference
            $shortClass = $this->getShortClassName($method);

            return "{$shortClass}::class";
        }

        [$class, $methodName] = explode('::', $method, 2);
        $shortClass           = $this->getShortClassName($class);

        return "{$shortClass}::class, '{$methodName}'";
    }

    /**
     * Get the short class name from a fully qualified class name.
     */
    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * Ensure the use statement for the attribute exists.
     */
    private function ensureUseStatement(string $code, string $attributeName): string
    {
        $useStatement = "use TestFlowLabs\\TestingAttributes\\{$attributeName};";

        // Check if use statement already exists
        if (str_contains($code, $useStatement)) {
            return $code;
        }

        // Check if a partial use statement exists
        if (str_contains($code, "TestFlowLabs\\TestingAttributes\\{$attributeName}")) {
            return $code;
        }

        // Find the position to insert the use statement
        $lines      = explode("\n", $code);
        $insertLine = null;

        // Look for existing use statements or namespace declaration
        foreach ($lines as $i => $line) {
            if (preg_match('/^use\s+/', $line)) {
                // Found a use statement, insert after the last one
                $insertLine = $i;
            } elseif (preg_match('/^namespace\s+/', $line) && $insertLine === null) {
                // Found namespace, insert after it (will be updated if use statements are found)
                $insertLine = $i + 1;
            }
        }

        if ($insertLine !== null) {
            // If inserting after namespace, add a blank line
            if (isset($lines[$insertLine - 1]) && preg_match('/^namespace\s+/', $lines[$insertLine - 1])) {
                array_splice($lines, $insertLine, 0, ['', $useStatement]);
            } else {
                array_splice($lines, $insertLine + 1, 0, [$useStatement]);
            }

            return implode("\n", $lines);
        }

        return $code;
    }
}
