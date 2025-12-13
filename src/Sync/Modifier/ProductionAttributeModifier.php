<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Modifier;

use TestFlowLabs\TestLink\Sync\SyncResult;
use TestFlowLabs\TestLink\Sync\ReverseTestedByAction;

/**
 * Modifies production files to inject or remove #[TestedBy] attributes.
 *
 * For production code, we inject attributes above the method declaration:
 *
 *     #[TestedBy(UserServiceTest::class, 'test_creates_user')]
 *     public function create(): User { }
 */
final class ProductionAttributeModifier
{
    /**
     * Apply reverse sync actions to production files.
     *
     * @param  list<ReverseTestedByAction>  $actions
     */
    public function apply(array $actions): SyncResult
    {
        /** @var array<string, list<string>> $modifiedFiles */
        $modifiedFiles = [];

        /** @var list<string> $errors */
        $errors = [];

        // Group actions by file
        $actionsByFile = $this->groupActionsByFile($actions);

        foreach ($actionsByFile as $filePath => $fileActions) {
            if (!file_exists($filePath)) {
                $errors[] = "File not found: {$filePath}";

                continue;
            }

            $code = file_get_contents($filePath);

            if ($code === false) {
                $errors[] = "Could not read file: {$filePath}";

                continue;
            }

            $modified     = false;
            $addedMethods = [];

            foreach ($fileActions as $action) {
                $newCode = $this->injectTestedBy($code, $action);

                if ($newCode !== $code) {
                    $code         = $newCode;
                    $modified     = true;
                    $addedMethods[] = $action->testIdentifier;
                }
            }

            if ($modified) {
                file_put_contents($filePath, $code);
                /** @var list<string> $uniqueMethods */
                $uniqueMethods            = array_values(array_unique($addedMethods));
                $modifiedFiles[$filePath] = $uniqueMethods;
            }
        }

        if ($errors !== []) {
            return SyncResult::withErrors($errors);
        }

        return SyncResult::applied($modifiedFiles);
    }

    /**
     * Inject #[TestedBy] attribute for a single action.
     */
    public function injectTestedBy(string $code, ReverseTestedByAction $action): string
    {
        $lines = explode("\n", $code);

        // Find the line where the method declaration starts
        $methodLine = $this->findMethodDeclarationLine($lines, $action->methodName);

        if ($methodLine === null) {
            return $code;
        }

        // Check if this #[TestedBy] already exists
        if ($this->hasTestedByAttribute($lines, $methodLine, $action->testIdentifier)) {
            return $code;
        }

        // Build attribute line
        $indent        = $this->detectIndentation($lines[$methodLine]);
        $attributeLine = $indent.$this->formatTestedByAttribute($action);

        // Check if we need to add use statement
        $code  = $this->ensureUseStatement($code);
        $lines = explode("\n", $code);

        // Recalculate method line after potential use statement addition
        $methodLine = $this->findMethodDeclarationLine($lines, $action->methodName);

        if ($methodLine === null) {
            return $code;
        }

        // Find the first line that's part of the method (could be docblock, attributes, or method itself)
        $insertLine = $this->findInsertionPoint($lines, $methodLine);

        // Insert attribute before the method (or after existing attributes)
        array_splice($lines, $insertLine, 0, [$attributeLine]);

        return implode("\n", $lines);
    }

    /**
     * Remove #[TestedBy] attributes for specific tests.
     *
     * @param  list<string>  $testIdentifiers
     */
    public function removeTestedBy(string $code, string $methodName, array $testIdentifiers): string
    {
        $lines         = explode("\n", $code);
        $filteredLines = [];

        foreach ($lines as $line) {
            $shouldRemove = false;

            // Check if line contains a TestedBy attribute
            if (preg_match('/#\[TestedBy\s*\(/', $line)) {
                foreach ($testIdentifiers as $testIdentifier) {
                    $testClass  = $this->getShortClassName($this->extractClassName($testIdentifier));
                    $testMethod = $this->extractMethodName($testIdentifier);

                    // Check for class::class reference
                    // If there's a method, check for it too
                    if (str_contains($line, "{$testClass}::class") && ($testMethod === null || str_contains($line, "'{$testMethod}'"))) {
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
     * Format the #[TestedBy] attribute.
     */
    private function formatTestedByAttribute(ReverseTestedByAction $action): string
    {
        $testClass  = $this->getShortClassName($action->getTestClassName());
        $testMethod = $action->getTestMethodName();

        if ($testMethod !== null) {
            return "#[TestedBy({$testClass}::class, '{$testMethod}')]";
        }

        return "#[TestedBy({$testClass}::class)]";
    }

    /**
     * Check if #[TestedBy] attribute already exists for the test.
     *
     * @param  list<string>  $lines
     */
    private function hasTestedByAttribute(array $lines, int $methodLine, string $testIdentifier): bool
    {
        $testClass  = $this->getShortClassName($this->extractClassName($testIdentifier));
        $testMethod = $this->extractMethodName($testIdentifier);

        // Look at lines before the method for existing attributes
        for ($i = max(0, $methodLine - 20); $i < $methodLine; $i++) {
            $line = $lines[$i];

            if (preg_match('/#\[TestedBy\s*\(/', $line) && str_contains($line, "{$testClass}::class")) {
                if ($testMethod === null || str_contains($line, "'{$testMethod}'")) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find the line index where the method declaration starts.
     *
     * @param  list<string>  $lines
     */
    private function findMethodDeclarationLine(array $lines, string $methodName): ?int
    {
        foreach ($lines as $i => $line) {
            // Look for: public function methodName, protected static function methodName, etc.
            // Handles visibility (public/protected/private), static modifier, and readonly/final modifiers
            if (preg_match('/^\s*(public|protected|private)?\s*(static)?\s*(readonly)?\s*(final)?\s*function\s+'.preg_quote($methodName, '/').'\s*\(/', $line)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Find the insertion point for a new attribute.
     *
     * Goes backward from the method line to find the first line that's part of the method block.
     *
     * @param  list<string>  $lines
     */
    private function findInsertionPoint(array $lines, int $methodLine): int
    {
        // Go backward to find existing attributes or docblock
        $insertLine = $methodLine;

        for ($i = $methodLine - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if ($line === '') {
                break;
            }

            // If it's an attribute, we want to insert after existing attributes
            if (str_starts_with($line, '#[')) {
                // Keep going back to find all attributes
                continue;
            }

            // If it's a docblock end, insert before the docblock
            if ($line === '*/') {
                // Find the start of the docblock
                for ($j = $i - 1; $j >= 0; $j--) {
                    if (str_starts_with(trim($lines[$j]), '/**')) {
                        $insertLine = $j;
                        break;
                    }
                }
                break;
            }

            // If it's an existing attribute line, continue looking back
            if (preg_match('/^\s*#\[/', $lines[$i])) {
                $insertLine = $i;

                continue;
            }

            // Otherwise, we've found a non-method line, stop
            break;
        }

        return $insertLine;
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
     * Extract the class name from a test identifier.
     */
    private function extractClassName(string $testIdentifier): string
    {
        if (str_contains($testIdentifier, '::')) {
            [$class] = explode('::', $testIdentifier, 2);

            return $class;
        }

        return $testIdentifier;
    }

    /**
     * Extract the method name from a test identifier.
     */
    private function extractMethodName(string $testIdentifier): ?string
    {
        if (str_contains($testIdentifier, '::')) {
            [, $method] = explode('::', $testIdentifier, 2);

            return $method;
        }

        return null;
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
     * Ensure the use statement for TestedBy exists.
     */
    private function ensureUseStatement(string $code): string
    {
        $useStatement = 'use TestFlowLabs\\TestingAttributes\\TestedBy;';

        // Check if use statement already exists
        if (str_contains($code, $useStatement)) {
            return $code;
        }

        // Check if a partial use statement exists
        if (str_contains($code, \TestFlowLabs\TestingAttributes\TestedBy::class)) {
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

    /**
     * Group actions by file path.
     *
     * @param  list<ReverseTestedByAction>  $actions
     *
     * @return array<string, list<ReverseTestedByAction>>
     */
    private function groupActionsByFile(array $actions): array
    {
        $grouped = [];

        foreach ($actions as $action) {
            $grouped[$action->productionFile][] = $action;
        }

        return $grouped;
    }
}
