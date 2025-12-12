<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

/**
 * Validates and optionally fixes @see tags to use FQCN format.
 *
 * Scans files for @see tags that don't use fully qualified class names
 * and can resolve them using use statements.
 */
final class FqcnValidator
{
    public function __construct(private readonly UseStatementResolver $resolver = new UseStatementResolver()) {}

    /**
     * Validate @see tags in a registry for FQCN format.
     */
    public function validate(SeeTagRegistry $seeRegistry): FqcnIssueRegistry
    {
        $issueRegistry = new FqcnIssueRegistry();

        // Check production @see tags
        foreach ($seeRegistry->getAllProductionSeeTags() as $entries) {
            foreach ($entries as $entry) {
                $this->validateEntry($entry, $issueRegistry);
            }
        }

        // Check test @see tags
        foreach ($seeRegistry->getAllTestSeeTags() as $entries) {
            foreach ($entries as $entry) {
                $this->validateEntry($entry, $issueRegistry);
            }
        }

        return $issueRegistry;
    }

    /**
     * Validate a single @see tag entry.
     */
    private function validateEntry(SeeTagEntry $entry, FqcnIssueRegistry $registry): void
    {
        // Skip if already FQCN
        if (FqcnIssue::isFqcn($entry->reference)) {
            return;
        }

        // Try to resolve the reference
        $result = $this->resolver->resolve($entry->reference, $entry->filePath);

        $issue = new FqcnIssue(
            originalReference: $entry->reference,
            resolvedFqcn: $result['fqcn'],
            filePath: $entry->filePath,
            line: $entry->line,
            context: $entry->context,
            methodName: $entry->methodName,
            className: $entry->className,
            isResolvable: $result['fqcn'] !== null,
            errorMessage: $result['error'],
        );

        $registry->register($issue);
    }

    /**
     * Fix FQCN issues in files.
     *
     * @return array{fixed: int, files: array<string, list<string>>, errors: list<string>}
     */
    public function fix(FqcnIssueRegistry $issueRegistry, bool $dryRun = false): array
    {
        $fixed      = 0;
        $fixedFiles = [];
        $errors     = [];

        // Group fixable issues by file
        $issuesByFile = [];

        foreach ($issueRegistry->getFixableIssues() as $issue) {
            if (!isset($issuesByFile[$issue->filePath])) {
                $issuesByFile[$issue->filePath] = [];
            }

            $issuesByFile[$issue->filePath][] = $issue;
        }

        foreach ($issuesByFile as $filePath => $issues) {
            $result = $this->fixFile($filePath, $issues, $dryRun);

            if ($result['error'] !== null) {
                $errors[] = $result['error'];

                continue;
            }

            if ($result['changed']) {
                $fixed += count($issues);
                $fixedFiles[$filePath] = array_map(
                    fn (FqcnIssue $i): string => $i->originalReference.' => '.$i->resolvedFqcn,
                    $issues
                );
            }
        }

        return [
            'fixed'  => $fixed,
            'files'  => $fixedFiles,
            'errors' => $errors,
        ];
    }

    /**
     * Fix FQCN issues in a single file.
     *
     * @param  list<FqcnIssue>  $issues
     *
     * @return array{changed: bool, error: string|null}
     */
    private function fixFile(string $filePath, array $issues, bool $dryRun): array
    {
        if (!file_exists($filePath)) {
            return ['changed' => false, 'error' => "File not found: {$filePath}"];
        }

        $code = file_get_contents($filePath);

        if ($code === false) {
            return ['changed' => false, 'error' => "Could not read file: {$filePath}"];
        }

        $changed = false;

        // Sort issues by line number (descending) to avoid offset issues
        usort($issues, fn (FqcnIssue $a, FqcnIssue $b): int => $b->line <=> $a->line);

        foreach ($issues as $issue) {
            if ($issue->resolvedFqcn === null) {
                continue;
            }

            // Replace the short reference with FQCN in the code
            $code = $this->replaceReference(
                $code,
                $issue->originalReference,
                $issue->resolvedFqcn,
                $issue->line
            );
            $changed = true;
        }

        if ($changed && !$dryRun) {
            $written = file_put_contents($filePath, $code);

            if ($written === false) {
                return ['changed' => false, 'error' => "Could not write file: {$filePath}"];
            }
        }

        return ['changed' => $changed, 'error' => null];
    }

    /**
     * Replace a @see reference in code.
     *
     * The line number is the method start line, so the @see tag may be
     * in the docblock above it. We search lines above the method for the @see tag.
     */
    private function replaceReference(
        string $code,
        string $original,
        string $replacement,
        int $line,
    ): string {
        $lines = explode("\n", $code);

        // Pattern: @see followed by the original reference
        $escapedOriginal = preg_quote($original, '/');
        $pattern         = '/(@see\s+)'.$escapedOriginal.'(\s|$)/';

        // Search for the @see tag in lines near the method (docblock is typically above)
        // Start from 20 lines above the method and search down to the method line
        $startLine = max(0, $line - 20);
        $endLine   = min(count($lines), $line);

        for ($i = $startLine; $i < $endLine; $i++) {
            if (preg_match($pattern, $lines[$i])) {
                $lines[$i] = preg_replace(
                    $pattern,
                    '$1'.$replacement.'$2',
                    $lines[$i],
                    1
                ) ?? $lines[$i];

                return implode("\n", $lines);
            }
        }

        return $code;
    }
}
