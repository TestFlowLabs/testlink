<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

/**
 * Registry for FQCN validation issues.
 *
 * Collects non-FQCN @see tags for reporting and fixing.
 */
final class FqcnIssueRegistry
{
    /** @var array<string, list<FqcnIssue>> file path => issues */
    private array $issuesByFile = [];

    /**
     * Register an FQCN issue.
     */
    public function register(FqcnIssue $issue): void
    {
        if (!isset($this->issuesByFile[$issue->filePath])) {
            $this->issuesByFile[$issue->filePath] = [];
        }

        $this->issuesByFile[$issue->filePath][] = $issue;
    }

    /**
     * Get all issues for a file.
     *
     * @return list<FqcnIssue>
     */
    public function getIssuesForFile(string $filePath): array
    {
        return $this->issuesByFile[$filePath] ?? [];
    }

    /**
     * Get all issues grouped by file.
     *
     * @return array<string, list<FqcnIssue>>
     */
    public function getAllByFile(): array
    {
        return $this->issuesByFile;
    }

    /**
     * Get all fixable issues.
     *
     * @return list<FqcnIssue>
     */
    public function getFixableIssues(): array
    {
        $fixable = [];

        foreach ($this->issuesByFile as $issues) {
            foreach ($issues as $issue) {
                if ($issue->isFixable()) {
                    $fixable[] = $issue;
                }
            }
        }

        return $fixable;
    }

    /**
     * Get all unfixable issues (cannot be resolved).
     *
     * @return list<FqcnIssue>
     */
    public function getUnfixableIssues(): array
    {
        $unfixable = [];

        foreach ($this->issuesByFile as $issues) {
            foreach ($issues as $issue) {
                if (!$issue->isFixable()) {
                    $unfixable[] = $issue;
                }
            }
        }

        return $unfixable;
    }

    /**
     * Count total issues.
     */
    public function count(): int
    {
        $count = 0;

        foreach ($this->issuesByFile as $issues) {
            $count += count($issues);
        }

        return $count;
    }

    /**
     * Count fixable issues.
     */
    public function countFixable(): int
    {
        return count($this->getFixableIssues());
    }

    /**
     * Check if there are any issues.
     */
    public function hasIssues(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Clear all issues.
     */
    public function clear(): void
    {
        $this->issuesByFile = [];
    }
}
