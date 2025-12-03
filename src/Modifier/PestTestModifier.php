<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Modifier;

use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;
use TestFlowLabs\TestLink\Contract\TestModifierInterface;

/**
 * Modifies Pest test files to inject or remove link method calls.
 *
 * For Pest tests, we inject method chains:
 *
 *     test('creates user')
 *         ->linksAndCovers(UserService::class.'::create');
 */
final class PestTestModifier implements TestModifierInterface
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
        // Filter out methods that already have the link
        $methodsToAdd = array_values(array_filter(
            $methods,
            fn (string $method): bool => !$testCase->hasCoversMethod($method)
        ));

        if ($methodsToAdd === []) {
            return $code;
        }

        $methodName = $withCoverage ? 'linksAndCovers' : 'links';

        return $this->injectPestChain($code, $testCase, $methodsToAdd, $methodName);
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

            // Check if line contains a link method call
            if (preg_match('/->(links|linksAndCovers)\s*\(/', $line)) {
                foreach ($methods as $method) {
                    $formatted = $this->formatMethodReference($method);

                    if (str_contains($line, $formatted) || str_contains($line, "'{$method}'")) {
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
        return $testCase->isPest();
    }

    /**
     * Inject link method calls into a Pest test chain.
     *
     * @param  list<string>  $methods
     */
    private function injectPestChain(
        string $code,
        ParsedTestCase $testCase,
        array $methods,
        string $methodName,
    ): string {
        $lines = explode("\n", $code);

        // Find the line with the semicolon that ends this test
        $insertionLine = $this->findInsertionLine($lines, $testCase->endLine);

        if ($insertionLine === null) {
            return $code;
        }

        // Build the chain calls
        $chains = [];

        foreach ($methods as $method) {
            $formattedMethod = $this->formatMethodReference($method);
            $chains[]        = "->{$methodName}({$formattedMethod})";
        }

        $chainString = implode('', $chains);

        // Insert before the semicolon
        $line                  = $lines[$insertionLine];
        $lines[$insertionLine] = $this->insertBeforeSemicolon($line, $chainString);

        return implode("\n", $lines);
    }

    /**
     * Find the line index where the test ends (with semicolon).
     *
     * @param  list<string>  $lines
     */
    private function findInsertionLine(array $lines, int $endLine): ?int
    {
        $lineIndex = $endLine - 1; // Convert to 0-based index

        // Search around the end line for the semicolon
        for ($i = $lineIndex; $i < min($lineIndex + 5, count($lines)); $i++) {
            if (str_contains($lines[$i], ';')) {
                return $i;
            }
        }

        // Also check the endLine itself
        if (isset($lines[$lineIndex]) && str_contains($lines[$lineIndex], ';')) {
            return $lineIndex;
        }

        return null;
    }

    /**
     * Insert a string before the semicolon in a line.
     */
    private function insertBeforeSemicolon(string $line, string $insert): string
    {
        $semicolonPos = strrpos($line, ';');

        if ($semicolonPos === false) {
            return $line;
        }

        return substr($line, 0, $semicolonPos).$insert.substr($line, $semicolonPos);
    }

    /**
     * Format a method reference for code output.
     *
     * Input: "App\Services\UserService::create"
     * Output: "App\Services\UserService::class.'::create'"
     */
    private function formatMethodReference(string $method): string
    {
        if (!str_contains($method, '::')) {
            // Class-level reference
            return "'{$method}'";
        }

        [$class, $methodName] = explode('::', $method, 2);

        // Use class constant reference for better IDE support
        return "{$class}::class.'::{$methodName}'";
    }
}
