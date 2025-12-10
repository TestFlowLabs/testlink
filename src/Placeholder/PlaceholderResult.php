<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Placeholder;

/**
 * Result object for placeholder resolution and pairing operations.
 *
 * Contains the actions to perform and any errors encountered during resolution.
 */
final readonly class PlaceholderResult
{
    /**
     * @param  list<PlaceholderAction>  $actions  Actions to perform (one per production-test pair)
     * @param  list<string>  $errors  Error messages for orphan placeholders or invalid entries
     * @param  list<string>  $warnings  Warning messages for non-fatal issues
     */
    public function __construct(
        public array $actions = [],
        public array $errors = [],
        public array $warnings = [],
    ) {}

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Check if there are any actions to perform.
     */
    public function hasActions(): bool
    {
        return count($this->actions) > 0;
    }

    /**
     * Get the total number of actions.
     */
    public function getActionCount(): int
    {
        return count($this->actions);
    }

    /**
     * Get the total number of errors.
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get the total number of warnings.
     */
    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    /**
     * Get unique production files that will be modified.
     *
     * @return list<string>
     */
    public function getProductionFilesToModify(): array
    {
        $files = array_unique(
            array_map(
                fn (PlaceholderAction $action): string => $action->getProductionFilePath(),
                $this->actions
            )
        );

        sort($files);

        return $files;
    }

    /**
     * Get unique test files that will be modified.
     *
     * @return list<string>
     */
    public function getTestFilesToModify(): array
    {
        $files = array_unique(
            array_map(
                fn (PlaceholderAction $action): string => $action->getTestFilePath(),
                $this->actions
            )
        );

        sort($files);

        return $files;
    }

    /**
     * Get actions grouped by placeholder ID.
     *
     * @return array<string, list<PlaceholderAction>>
     */
    public function getActionsByPlaceholder(): array
    {
        $grouped = [];

        foreach ($this->actions as $action) {
            $grouped[$action->placeholderId][] = $action;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * Get actions for a specific production file.
     *
     * @return list<PlaceholderAction>
     */
    public function getActionsForProductionFile(string $filePath): array
    {
        return array_values(
            array_filter(
                $this->actions,
                fn (PlaceholderAction $action): bool => $action->getProductionFilePath() === $filePath
            )
        );
    }

    /**
     * Get actions for a specific test file.
     *
     * @return list<PlaceholderAction>
     */
    public function getActionsForTestFile(string $filePath): array
    {
        return array_values(
            array_filter(
                $this->actions,
                fn (PlaceholderAction $action): bool => $action->getTestFilePath() === $filePath
            )
        );
    }

    /**
     * Get a summary of the result.
     *
     * @return array{placeholders: int, actions: int, production_files: int, test_files: int, errors: int, warnings: int}
     */
    public function getSummary(): array
    {
        return [
            'placeholders'     => count($this->getActionsByPlaceholder()),
            'actions'          => $this->getActionCount(),
            'production_files' => count($this->getProductionFilesToModify()),
            'test_files'       => count($this->getTestFilesToModify()),
            'errors'           => $this->getErrorCount(),
            'warnings'         => $this->getWarningCount(),
        ];
    }
}
