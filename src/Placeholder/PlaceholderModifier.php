<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Placeholder;

/**
 * Modifies source files to replace placeholders with real links.
 *
 * Handles both production files (#[TestedBy]) and test files
 * (linksAndCovers/links for Pest, #[LinksAndCovers]/#[Links] for PHPUnit).
 */
final class PlaceholderModifier
{
    /**
     * Apply all actions to their respective files.
     *
     * Groups actions by file and applies them in batch.
     *
     * @param  list<PlaceholderAction>  $actions
     * @param  bool  $dryRun  If true, returns what would change without modifying files
     *
     * @return array{modified_files: list<string>, changes: list<array{file: string, placeholder: string, replacement: string}>}
     */
    public function apply(array $actions, bool $dryRun = false): array
    {
        $modifiedFiles = [];
        $changes       = [];

        // Group actions by production file
        $productionActionsByFile = [];
        foreach ($actions as $action) {
            $file                             = $action->getProductionFilePath();
            $productionActionsByFile[$file][] = $action;
        }

        // Group actions by test file
        $testActionsByFile = [];
        foreach ($actions as $action) {
            $file                       = $action->getTestFilePath();
            $testActionsByFile[$file][] = $action;
        }

        // Process production files
        foreach ($productionActionsByFile as $filePath => $fileActions) {
            $result = $this->processProductionFile($filePath, $fileActions, $dryRun);
            if ($result !== null) {
                $modifiedFiles[] = $filePath;
                $changes         = [...$changes, ...$result];
            }
        }

        // Process test files
        foreach ($testActionsByFile as $filePath => $fileActions) {
            $result = $this->processTestFile($filePath, $fileActions, $dryRun);
            if ($result !== null) {
                $modifiedFiles[] = $filePath;
                $changes         = [...$changes, ...$result];
            }
        }

        return [
            'modified_files' => array_values(array_unique($modifiedFiles)),
            'changes'        => $changes,
        ];
    }

    /**
     * Process a production file to replace placeholder TestedBy attributes.
     *
     * @param  list<PlaceholderAction>  $actions
     *
     * @return list<array{file: string, placeholder: string, replacement: string}>|null
     */
    private function processProductionFile(string $filePath, array $actions, bool $dryRun): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $code = file_get_contents($filePath);
        if ($code === false) {
            return null;
        }

        $changes      = [];
        $originalCode = $code;

        // Group actions by placeholder AND production method (to handle N:M correctly)
        // Key: "placeholder|production_identifier" to group tests for each production method
        $actionsByPlaceholderAndMethod = [];
        foreach ($actions as $action) {
            $key                                  = $action->placeholderId.'|'.$action->getProductionMethodIdentifier();
            $actionsByPlaceholderAndMethod[$key][] = $action;
        }

        // Process each placeholder-method combination
        foreach ($actionsByPlaceholderAndMethod as $key => $methodActions) {
            [$placeholderId] = explode('|', $key, 2);

            // Get unique test identifiers for this specific production method
            $testIdentifiers = array_unique(array_map(
                fn (PlaceholderAction $a): string => $a->getTestIdentifier(),
                $methodActions
            ));

            $result = $this->replaceProductionPlaceholder($code, $placeholderId, array_values($testIdentifiers));
            if ($result['changed']) {
                $code      = $result['code'];
                $changes[] = [
                    'file'        => $filePath,
                    'placeholder' => $placeholderId,
                    'replacement' => implode(', ', $testIdentifiers),
                ];
            }
        }

        if ($code !== $originalCode && !$dryRun) {
            file_put_contents($filePath, $code);
        }

        return $changes !== [] ? $changes : null;
    }

    /**
     * Process a test file to replace placeholder links.
     *
     * @param  list<PlaceholderAction>  $actions
     *
     * @return list<array{file: string, placeholder: string, replacement: string}>|null
     */
    private function processTestFile(string $filePath, array $actions, bool $dryRun): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $code = file_get_contents($filePath);
        if ($code === false) {
            return null;
        }

        $changes      = [];
        $originalCode = $code;

        // Determine if this is a Pest or PHPUnit file
        $isPest = $this->isPestFile($code);

        // Group actions by placeholder AND test identifier (to handle N:M correctly)
        // Key: "placeholder|test_identifier" to group production methods for each test
        $actionsByPlaceholderAndTest = [];
        foreach ($actions as $action) {
            $key                                = $action->placeholderId.'|'.$action->getTestIdentifier();
            $actionsByPlaceholderAndTest[$key][] = $action;
        }

        // Process each placeholder-test combination
        foreach ($actionsByPlaceholderAndTest as $key => $testActions) {
            [$placeholderId] = explode('|', $key, 2);

            // Get unique production methods for this specific test
            $productionMethods = array_unique(array_map(
                fn (PlaceholderAction $a): string => $a->getProductionMethodIdentifier(),
                $testActions
            ));

            if ($isPest) {
                $result = $this->replacePestPlaceholder($code, $placeholderId, array_values($productionMethods));
            } else {
                $result = $this->replacePhpUnitPlaceholder($code, $placeholderId, array_values($productionMethods));
            }

            if ($result['changed']) {
                $code      = $result['code'];
                $changes[] = [
                    'file'        => $filePath,
                    'placeholder' => $placeholderId,
                    'replacement' => implode(', ', $productionMethods),
                ];
            }
        }

        if ($code !== $originalCode && !$dryRun) {
            file_put_contents($filePath, $code);
        }

        return $changes !== [] ? $changes : null;
    }

    /**
     * Replace placeholder in production #[TestedBy] attributes.
     *
     * @param  list<string>  $testIdentifiers
     *
     * @return array{code: string, changed: bool}
     */
    private function replaceProductionPlaceholder(string $code, string $placeholderId, array $testIdentifiers): array
    {
        $changed = false;

        // Pattern to match #[TestedBy('@placeholder')]
        $escapedPlaceholder = preg_quote($placeholderId, '/');
        $pattern            = "/#\[TestedBy\s*\(\s*['\"]".$escapedPlaceholder."['\"]\s*\)\]/";

        if (preg_match($pattern, $code)) {
            // Build replacement attributes
            $replacements = [];
            foreach ($testIdentifiers as $testId) {
                [$testClass, $testMethod] = $this->parseTestIdentifier($testId);
                $replacements[]           = $testMethod !== null ? "#[TestedBy('{$testClass}', '{$testMethod}')]" : "#[TestedBy('{$testClass}')]";
            }

            $replacement = implode("\n    ", $replacements);
            $result      = preg_replace($pattern, $replacement, $code, 1);
            if ($result !== null) {
                $code    = $result;
                $changed = true;
            }
        }

        return ['code' => $code, 'changed' => $changed];
    }

    /**
     * Replace placeholder in Pest linksAndCovers/links calls.
     *
     * @param  list<string>  $productionMethods
     *
     * @return array{code: string, changed: bool}
     */
    private function replacePestPlaceholder(string $code, string $placeholderId, array $productionMethods): array
    {
        $changed = false;

        // Pattern to match ->linksAndCovers('@placeholder') or ->links('@placeholder')
        $escapedPlaceholder = preg_quote($placeholderId, '/');
        $pattern            = "/->(linksAndCovers|links)\s*\(\s*['\"]".$escapedPlaceholder."['\"]\s*\)/";

        if (preg_match($pattern, $code, $matches)) {
            $methodName = $matches[1]; // 'linksAndCovers' or 'links'

            // Build replacement chain calls
            $replacements = [];
            foreach ($productionMethods as $method) {
                $formatted      = $this->formatPestMethodReference($method);
                $replacements[] = "->{$methodName}({$formatted})";
            }

            $replacement = implode('', $replacements);
            $result      = preg_replace($pattern, $replacement, $code, 1);
            if ($result !== null) {
                $code    = $result;
                $changed = true;
            }
        }

        return ['code' => $code, 'changed' => $changed];
    }

    /**
     * Replace placeholder in PHPUnit #[LinksAndCovers] or #[Links] attributes.
     *
     * @param  list<string>  $productionMethods
     *
     * @return array{code: string, changed: bool}
     */
    private function replacePhpUnitPlaceholder(string $code, string $placeholderId, array $productionMethods): array
    {
        $changed = false;

        // Pattern to match #[LinksAndCovers('@placeholder')] or #[Links('@placeholder')]
        $escapedPlaceholder = preg_quote($placeholderId, '/');
        $pattern            = "/#\[(LinksAndCovers|Links)\s*\(\s*['\"]".$escapedPlaceholder."['\"]\s*\)\]/";

        if (preg_match($pattern, $code, $matches)) {
            $attributeName = $matches[1]; // 'LinksAndCovers' or 'Links'

            // Build replacement attributes
            $replacements = [];
            foreach ($productionMethods as $method) {
                $formatted      = $this->formatPhpUnitAttributeArguments($method);
                $replacements[] = "#[{$attributeName}({$formatted})]";
            }

            // Detect indentation from the matched line
            $indent = preg_match('/^(\s*)#\[(LinksAndCovers|Links)/m', $code, $indentMatch) ? $indentMatch[1] : '    ';

            $replacement = implode("\n".$indent, $replacements);
            $result      = preg_replace($pattern, $replacement, $code, 1);
            if ($result !== null) {
                $code    = $result;
                $changed = true;

                // Ensure use statements exist
                $code = $this->ensurePhpUnitUseStatements($code, $productionMethods);
            }
        }

        return ['code' => $code, 'changed' => $changed];
    }

    /**
     * Parse a test identifier into class and method.
     *
     * @return array{0: string, 1: string|null}
     */
    private function parseTestIdentifier(string $identifier): array
    {
        if (str_contains($identifier, '::')) {
            [$class, $method] = explode('::', $identifier, 2);

            return [$class, $method];
        }

        return [$identifier, null];
    }

    /**
     * Format method reference for Pest code.
     *
     * Input: "App\Services\UserService::create"
     * Output: "App\Services\UserService::class.'::create'"
     */
    private function formatPestMethodReference(string $method): string
    {
        if (!str_contains($method, '::')) {
            return "'{$method}'";
        }

        [$class, $methodName] = explode('::', $method, 2);

        return "{$class}::class.'::{$methodName}'";
    }

    /**
     * Format method reference for PHPUnit attributes.
     *
     * Input: "App\Services\UserService::create"
     * Output: "UserService::class, 'create'"
     */
    private function formatPhpUnitAttributeArguments(string $method): string
    {
        if (!str_contains($method, '::')) {
            $shortClass = $this->getShortClassName($method);

            return "{$shortClass}::class";
        }

        [$class, $methodName] = explode('::', $method, 2);
        $shortClass           = $this->getShortClassName($class);

        return "{$shortClass}::class, '{$methodName}'";
    }

    /**
     * Get short class name from FQCN.
     */
    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    /**
     * Check if code is a Pest test file.
     */
    private function isPestFile(string $code): bool
    {
        return preg_match('/\b(test|it)\s*\(/', $code) === 1;
    }

    /**
     * Ensure use statements for production classes in PHPUnit tests.
     *
     * @param  list<string>  $productionMethods
     */
    private function ensurePhpUnitUseStatements(string $code, array $productionMethods): string
    {
        $lines        = explode("\n", $code);
        $insertLine   = null;
        $existingUses = [];

        // Find existing use statements and insertion point
        foreach ($lines as $i => $line) {
            if (preg_match('/^use\s+(.+);/', $line, $matches)) {
                $existingUses[] = trim($matches[1]);
                $insertLine     = $i;
            } elseif (preg_match('/^namespace\s+/', $line) && $insertLine === null) {
                $insertLine = $i + 1;
            }
        }

        if ($insertLine === null) {
            return $code;
        }

        // Collect classes that need use statements
        $classesToAdd = [];
        foreach ($productionMethods as $method) {
            if (str_contains($method, '::')) {
                [$class] = explode('::', $method, 2);
            } else {
                $class = $method;
            }

            if (!in_array($class, $existingUses, true) && !in_array($class, $classesToAdd, true)) {
                $classesToAdd[] = $class;
            }
        }

        if ($classesToAdd === []) {
            return $code;
        }

        // Add use statements
        $useStatements = array_map(fn ($class): string => "use {$class};", $classesToAdd);

        if (isset($lines[$insertLine - 1]) && preg_match('/^namespace\s+/', $lines[$insertLine - 1])) {
            array_splice($lines, $insertLine, 0, ['', ...$useStatements]);
        } else {
            array_splice($lines, $insertLine + 1, 0, $useStatements);
        }

        return implode("\n", $lines);
    }
}
