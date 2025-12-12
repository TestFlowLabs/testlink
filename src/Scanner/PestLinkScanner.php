<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Scanner;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;
use TestFlowLabs\TestLink\Sync\Parser\PestTestParser;

/**
 * Scans Pest test files for linksAndCovers() and links() method chains.
 *
 * This scanner statically parses Pest test files to find coverage links,
 * without requiring the tests to be executed.
 */
final class PestLinkScanner
{
    private ?string $projectRoot = null;
    private readonly PestTestParser $parser;

    public function __construct()
    {
        $this->parser = new PestTestParser();
    }

    /**
     * Set the project root directory for filtering.
     */
    public function setProjectRoot(string $projectRoot): self
    {
        $this->projectRoot = $projectRoot;

        return $this;
    }

    /**
     * Scan all Pest test files and register links.
     */
    public function scan(TestLinkRegistry $registry): void
    {
        $testFiles = $this->discoverPestFiles();

        foreach ($testFiles as $filePath) {
            $this->scanFile($filePath, $registry);
        }
    }

    /**
     * Scan a single Pest test file and register links.
     */
    public function scanFile(string $filePath, TestLinkRegistry $registry): void
    {
        if (!$this->parser->supports($filePath)) {
            return;
        }

        $tests = $this->parser->parseFile($filePath);

        // Derive namespace from file path
        $namespace = $this->deriveNamespace($filePath);

        foreach ($tests as $test) {
            $testIdentifier = $this->buildTestIdentifier($namespace, $test->name, $test->describePath);

            foreach ($test->existingCoversMethod as $methodReference) {
                // Skip placeholders (they start with @)
                if (str_starts_with($methodReference, '@')) {
                    continue;
                }

                $registry->registerLink($testIdentifier, $methodReference);
            }
        }
    }

    /**
     * Discover all Pest test files in the project.
     *
     * @return list<string>
     */
    public function discoverPestFiles(): array
    {
        $projectRoot = $this->projectRoot ?? $this->detectProjectRoot();
        $testsDir    = $projectRoot.'/tests';

        if (!is_dir($testsDir)) {
            return [];
        }

        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getRealPath();

            if ($filePath === false) {
                continue;
            }

            // Skip vendor directory
            if (str_contains($filePath, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            // Only include Pest test files
            if ($this->parser->supports($filePath)) {
                $files[] = $filePath;
            }
        }

        return $files;
    }

    /**
     * Build a test identifier from namespace and test name.
     *
     * @param  list<string>  $describePath
     */
    private function buildTestIdentifier(string $namespace, string $testName, array $describePath = []): string
    {
        $fullTestName = $describePath !== []
            ? implode(' > ', $describePath).' > '.$testName
            : $testName;

        return "{$namespace}::{$fullTestName}";
    }

    /**
     * Derive namespace from file path.
     *
     * Attempts to read the namespace from the file, falling back to
     * generating one from the file path.
     */
    private function deriveNamespace(string $filePath): string
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return $this->namespaceFromPath($filePath);
        }

        // Try to find a uses() call with a class binding
        if (preg_match('/uses\s*\(\s*([^)]+)\s*\)/', $content, $matches)) {
            $usesContent = $matches[1];

            // Check for class binding like TestCase::class
            if (preg_match('/([A-Za-z0-9_\\\\]+)::class/', $usesContent, $classMatches)) {
                // Return the test file's logical namespace based on path
                return $this->namespaceFromPath($filePath);
            }
        }

        return $this->namespaceFromPath($filePath);
    }

    /**
     * Generate namespace from file path.
     */
    private function namespaceFromPath(string $filePath): string
    {
        $projectRoot  = $this->projectRoot ?? $this->detectProjectRoot();
        $relativePath = str_replace($projectRoot.'/', '', $filePath);

        // Remove .php extension
        $relativePath = preg_replace('/\.php$/', '', $relativePath);

        if ($relativePath === null) {
            return 'Tests';
        }

        // Convert path to namespace-like format
        // tests/Unit/ExampleTest -> Tests\Unit\ExampleTest
        $namespace = str_replace('/', '\\', $relativePath);

        // Capitalize 'tests' to 'Tests'
        if (str_starts_with($namespace, 'tests\\')) {
            return 'Tests\\'.substr($namespace, 6);
        }

        return $namespace;
    }

    /**
     * Detect the project root directory.
     */
    private function detectProjectRoot(): string
    {
        $directory = getcwd() ?: __DIR__;

        while ($directory !== '/') {
            if (file_exists($directory.'/composer.json')) {
                return $directory;
            }
            $directory = dirname($directory);
        }

        return getcwd() ?: __DIR__;
    }
}
