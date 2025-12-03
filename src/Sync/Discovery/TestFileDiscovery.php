<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Discovery;

use Composer\Autoload\ClassLoader;
use TestFlowLabs\TestLink\Sync\Exception\TestFileNotFoundException;

/**
 * Discovers test file paths from test class names.
 */
final class TestFileDiscovery
{
    private ?string $projectRoot = null;

    /**
     * Set the project root directory.
     */
    public function setProjectRoot(string $projectRoot): self
    {
        $this->projectRoot = $projectRoot;

        return $this;
    }

    /**
     * Find the file path for a test identifier.
     *
     * @param  string  $testIdentifier  Format: "Tests\Unit\UserServiceTest::test name"
     *
     * @throws TestFileNotFoundException
     */
    public function findTestFile(string $testIdentifier): string
    {
        $className = $this->extractClassName($testIdentifier);

        // Strategy 1: Check Composer classmap
        $file = $this->findViaClassmap($className);

        if ($file !== null) {
            return $file;
        }

        // Strategy 2: PSR-4 path resolution
        $file = $this->findViaPsr4($className);

        if ($file !== null) {
            return $file;
        }

        // Strategy 3: Glob pattern matching
        $file = $this->findViaGlob($className);

        if ($file !== null) {
            return $file;
        }

        throw new TestFileNotFoundException($testIdentifier, $className);
    }

    /**
     * Check if a test file exists for the given identifier.
     */
    public function testFileExists(string $testIdentifier): bool
    {
        try {
            $this->findTestFile($testIdentifier);

            return true;
        } catch (TestFileNotFoundException) {
            return false;
        }
    }

    /**
     * Extract the class name from a test identifier.
     */
    public function extractClassName(string $testIdentifier): string
    {
        $parts = explode('::', $testIdentifier, 2);

        return $parts[0];
    }

    /**
     * Extract the test name from a test identifier.
     */
    public function extractTestName(string $testIdentifier): string
    {
        $parts = explode('::', $testIdentifier, 2);

        return $parts[1] ?? '';
    }

    /**
     * Find file via Composer classmap.
     */
    private function findViaClassmap(string $className): ?string
    {
        $loader = $this->getComposerLoader();

        if (!$loader instanceof \Composer\Autoload\ClassLoader) {
            return null;
        }

        $classMap = $loader->getClassMap();
        $file     = $classMap[$className] ?? null;

        if ($file !== null && file_exists($file)) {
            return realpath($file) ?: $file;
        }

        return null;
    }

    /**
     * Find file via PSR-4 path resolution.
     */
    private function findViaPsr4(string $className): ?string
    {
        $projectRoot = $this->projectRoot ?? $this->detectProjectRoot();

        // Common test namespace prefixes
        $prefixes = [
            'Tests\\' => 'tests/',
            'Test\\'  => 'test/',
        ];

        foreach ($prefixes as $prefix => $basePath) {
            if (str_starts_with($className, $prefix)) {
                $relativePath = str_replace('\\', '/', substr($className, strlen($prefix))).'.php';
                $fullPath     = $projectRoot.'/'.$basePath.$relativePath;

                if (file_exists($fullPath)) {
                    return realpath($fullPath) ?: $fullPath;
                }
            }
        }

        // Try direct namespace to path conversion
        $relativePath = str_replace('\\', '/', $className).'.php';
        $candidates   = [
            $projectRoot.'/tests/'.$relativePath,
            $projectRoot.'/'.$relativePath,
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return realpath($path) ?: $path;
            }
        }

        return null;
    }

    /**
     * Find file via glob pattern matching.
     */
    private function findViaGlob(string $className): ?string
    {
        $projectRoot = $this->projectRoot ?? $this->detectProjectRoot();

        // Extract short class name
        $parts     = explode('\\', $className);
        $shortName = end($parts);

        // Search in tests directory
        $pattern = $projectRoot.'/tests/**/'.$shortName.'.php';
        $files   = glob($pattern, GLOB_BRACE);

        if ($files !== false && $files !== []) {
            return realpath($files[0]) ?: $files[0];
        }

        // Try without nested directories
        $pattern = $projectRoot.'/tests/'.$shortName.'.php';
        $files   = glob($pattern);

        if ($files !== false && $files !== []) {
            return realpath($files[0]) ?: $files[0];
        }

        return null;
    }

    /**
     * Get the Composer autoloader instance.
     */
    private function getComposerLoader(): ?ClassLoader
    {
        foreach (spl_autoload_functions() as $autoloader) {
            if (is_array($autoloader) && $autoloader[0] instanceof ClassLoader) {
                return $autoloader[0];
            }
        }

        return null;
    }

    /**
     * Detect the project root directory.
     */
    private function detectProjectRoot(): string
    {
        if ($this->projectRoot !== null) {
            return $this->projectRoot;
        }

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
