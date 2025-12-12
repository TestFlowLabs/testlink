<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

/**
 * Modifies PHP docblocks to add or remove @see tags.
 *
 * Preserves existing docblock formatting and indentation.
 * Handles creation of new docblocks for methods without one.
 */
final class DocBlockModifier
{
    public function __construct(private readonly DocBlockParser $parser = new DocBlockParser()) {}

    /**
     * Add @see tags to a method's docblock in source code.
     *
     * @param  string  $code  The full source code
     * @param  string  $methodName  The method to add @see tags to
     * @param  list<string>  $references  References to add (e.g., ["\Tests\UserTest::testCreate"])
     *
     * @return array{code: string, changed: bool}
     */
    public function addSeeTags(string $code, string $methodName, array $references): array
    {
        if ($references === []) {
            return ['code' => $code, 'changed' => false];
        }

        $lines      = $this->splitLines($code);
        $methodLine = $this->findMethodLine($lines, $methodName);

        if ($methodLine === null) {
            return ['code' => $code, 'changed' => false];
        }

        $indent       = $this->detectIndentation($lines[$methodLine]);
        $docBlockInfo = $this->findDocBlockInfo($lines, $methodLine);

        if ($docBlockInfo !== null) {
            // Existing docblock - add @see tags to it
            return $this->addSeeToExistingDocBlock(
                $lines,
                $docBlockInfo,
                $references,
                $indent
            );
        }

        // No docblock - create new one
        return $this->createDocBlockWithSee(
            $lines,
            $methodLine,
            $references,
            $indent
        );
    }

    /**
     * Remove @see tags from a method's docblock.
     *
     * @param  list<string>  $references  References to remove
     *
     * @return array{code: string, changed: bool}
     */
    public function removeSeeTags(string $code, string $methodName, array $references): array
    {
        if ($references === []) {
            return ['code' => $code, 'changed' => false];
        }

        $lines      = $this->splitLines($code);
        $methodLine = $this->findMethodLine($lines, $methodName);

        if ($methodLine === null) {
            return ['code' => $code, 'changed' => false];
        }

        $docBlockInfo = $this->findDocBlockInfo($lines, $methodLine);

        if ($docBlockInfo === null) {
            return ['code' => $code, 'changed' => false];
        }

        $changed        = false;
        $normalizedRefs = array_map(fn ($r): string => ltrim($r, '\\'), $references);

        // Remove @see lines that match references
        for ($i = $docBlockInfo['startLine']; $i <= $docBlockInfo['endLine']; $i++) {
            if ($this->isSeeTagLine($lines[$i])) {
                $lineRef           = $this->extractSeeReferenceFromLine($lines[$i]);
                $normalizedLineRef = ltrim($lineRef, '\\');

                if (in_array($normalizedLineRef, $normalizedRefs, true)) {
                    unset($lines[$i]);
                    $changed = true;
                }
            }
        }

        if (!$changed) {
            return ['code' => $code, 'changed' => false];
        }

        $lines = array_values($lines);
        $code  = $this->joinLines($lines);

        // Clean up potentially empty docblock
        $code = $this->cleanEmptyDocBlocks($code);

        return ['code' => $code, 'changed' => true];
    }

    /**
     * Remove all @see tags from a method's docblock.
     *
     * @return array{code: string, changed: bool}
     */
    public function removeAllSeeTags(string $code, string $methodName): array
    {
        $lines      = $this->splitLines($code);
        $methodLine = $this->findMethodLine($lines, $methodName);

        if ($methodLine === null) {
            return ['code' => $code, 'changed' => false];
        }

        $docBlockInfo = $this->findDocBlockInfo($lines, $methodLine);

        if ($docBlockInfo === null) {
            return ['code' => $code, 'changed' => false];
        }

        $changed = false;

        for ($i = $docBlockInfo['startLine']; $i <= $docBlockInfo['endLine']; $i++) {
            if ($this->isSeeTagLine($lines[$i])) {
                unset($lines[$i]);
                $changed = true;
            }
        }

        if (!$changed) {
            return ['code' => $code, 'changed' => false];
        }

        $lines = array_values($lines);
        $code  = $this->joinLines($lines);
        $code  = $this->cleanEmptyDocBlocks($code);

        return ['code' => $code, 'changed' => true];
    }

    /**
     * Split code into lines while preserving line endings.
     *
     * @return list<string>
     */
    private function splitLines(string $code): array
    {
        // Normalize line endings to \n for consistent handling
        $code = str_replace("\r\n", "\n", $code);
        $code = str_replace("\r", "\n", $code);

        return explode("\n", $code);
    }

    /**
     * Join lines back into code.
     *
     * @param  list<string>  $lines
     */
    private function joinLines(array $lines): string
    {
        return implode("\n", $lines);
    }

    /**
     * Find the line number where a method is declared.
     *
     * @param  list<string>  $lines
     */
    private function findMethodLine(array $lines, string $methodName): ?int
    {
        $escapedName = preg_quote($methodName, '/');

        foreach ($lines as $index => $line) {
            // Match method declarations: public/protected/private/static function methodName(
            if (preg_match(
                '/^\s*(?:(?:public|protected|private|static|final|abstract)\s+)*function\s+'.$escapedName.'\s*\(/i',
                $line
            )) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Detect indentation of a line.
     */
    private function detectIndentation(string $line): string
    {
        if (preg_match('/^(\s*)/', $line, $matches)) {
            return $matches[1];
        }

        return '    '; // Default: 4 spaces
    }

    /**
     * Find docblock information for a method.
     *
     * @param  list<string>  $lines
     *
     * @return array{startLine: int, endLine: int}|null
     */
    private function findDocBlockInfo(array $lines, int $methodLine): ?array
    {
        // Look backwards from method for docblock or attributes
        $endLine   = null;
        $startLine = null;

        for ($i = $methodLine - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if ($line === '') {
                continue;
            }

            // Skip attributes
            if (str_starts_with($line, '#[')) {
                continue;
            }

            // Found docblock end
            if (str_ends_with($line, '*/')) {
                $endLine = $i;

                // Find start of docblock
                for ($j = $i; $j >= 0; $j--) {
                    if (str_contains($lines[$j], '/**')) {
                        $startLine = $j;
                        break;
                    }
                }

                if ($startLine !== null) {
                    return [
                        'startLine' => $startLine,
                        'endLine'   => $endLine,
                    ];
                }

                return null;
            }

            // Non-docblock, non-attribute content found - no docblock
            break;
        }

        return null;
    }

    /**
     * Add @see tags to an existing docblock.
     *
     * @param  list<string>  $lines
     * @param  array{startLine: int, endLine: int}  $docBlockInfo
     * @param  list<string>  $references
     *
     * @return array{code: string, changed: bool}
     */
    private function addSeeToExistingDocBlock(
        array $lines,
        array $docBlockInfo,
        array $references,
        string $indent
    ): array {
        // Extract current docblock and check for duplicates
        $docBlockLines = array_slice(
            $lines,
            $docBlockInfo['startLine'],
            $docBlockInfo['endLine'] - $docBlockInfo['startLine'] + 1
        );
        $docBlock = $this->joinLines($docBlockLines);

        // Filter out references that already exist
        $referencesToAdd = [];
        foreach ($references as $ref) {
            if (!$this->parser->hasSeeReference($docBlock, $ref)) {
                $referencesToAdd[] = $ref;
            }
        }

        if ($referencesToAdd === []) {
            return ['code' => $this->joinLines($lines), 'changed' => false];
        }

        // Find insertion point (before closing */)
        $endLine = $docBlockInfo['endLine'];

        // Build @see lines
        $seeLines = [];

        // Check if we need an empty line before @see section
        $needsEmptyLine = $this->needsEmptyLineBeforeSee($docBlockLines);
        if ($needsEmptyLine) {
            $seeLines[] = $indent.' *';
        }

        foreach ($referencesToAdd as $reference) {
            $seeLines[] = $indent.' * @see '.$reference;
        }

        // Insert @see lines before closing */
        array_splice($lines, $endLine, 0, $seeLines);

        return ['code' => $this->joinLines($lines), 'changed' => true];
    }

    /**
     * Check if we need an empty line before @see section.
     *
     * @param  list<string>  $docBlockLines
     */
    private function needsEmptyLineBeforeSee(array $docBlockLines): bool
    {
        // If docblock only has /** and */, no empty line needed
        if (count($docBlockLines) <= 2) {
            return false;
        }

        // Check if last content line (before */) has content
        // At this point count >= 3, so lastContentIndex >= 1
        $lastContentIndex = count($docBlockLines) - 2;
        $lastLine         = trim($docBlockLines[$lastContentIndex]);

        // If last line is empty (* only) or already a @see, no extra line needed
        return $lastLine !== '*' && !str_contains($lastLine, '@see');
    }

    /**
     * Create a new docblock with @see tags.
     *
     * @param  list<string>  $lines
     * @param  list<string>  $references
     *
     * @return array{code: string, changed: bool}
     */
    private function createDocBlockWithSee(
        array $lines,
        int $methodLine,
        array $references,
        string $indent
    ): array {
        $docBlock = [$indent.'/**'];

        foreach ($references as $reference) {
            $docBlock[] = $indent.' * @see '.$reference;
        }

        $docBlock[] = $indent.' */';

        // Find insertion point (before attributes if any, or at method line)
        $insertLine = $this->findInsertionPoint($lines, $methodLine);

        array_splice($lines, $insertLine, 0, $docBlock);

        return ['code' => $this->joinLines($lines), 'changed' => true];
    }

    /**
     * Find the line to insert a new docblock.
     *
     * @param  list<string>  $lines
     */
    private function findInsertionPoint(array $lines, int $methodLine): int
    {
        // Look backwards for attributes
        for ($i = $methodLine - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if ($line === '') {
                continue;
            }

            // Skip attributes (continue looking)
            if (str_starts_with($line, '#[')) {
                continue;
            }

            // Found non-attribute content, insert after this line
            return $i + 1;
        }

        // No attributes found, insert at method line
        return $methodLine;
    }

    /**
     * Check if a line contains a @see tag.
     */
    private function isSeeTagLine(string $line): bool
    {
        return (bool) preg_match('/^\s*\*\s*@see\s/', $line);
    }

    /**
     * Extract @see reference from a docblock line.
     */
    private function extractSeeReferenceFromLine(string $line): string
    {
        if (preg_match('/^\s*\*\s*@see\s+(\S+)/', $line, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Remove empty docblocks from code.
     */
    private function cleanEmptyDocBlocks(string $code): string
    {
        // Remove docblocks that only contain whitespace or empty * lines
        // Pattern matches: /** followed by only whitespace/* lines, then */
        return preg_replace(
            '/\n?\s*\/\*\*\s*\n(?:\s*\*\s*\n)*\s*\*\/\s*\n?/s',
            "\n",
            $code
        ) ?? $code;
    }
}
