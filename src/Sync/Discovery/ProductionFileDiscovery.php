<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Discovery;

use ReflectionClass;
use Composer\Autoload\ClassLoader;
use TestFlowLabs\TestLink\Sync\Exception\ProductionFileNotFoundException;

/**
 * Discovers production file paths from class names.
 */
final class ProductionFileDiscovery
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
     * Find the file path for a method identifier.
     *
     * @param  string  $methodIdentifier  Format: "App\Services\UserService::create"
     *
     * @throws ProductionFileNotFoundException
     */
    public function findProductionFile(string $methodIdentifier): string
    {
        $className = $this->extractClassName($methodIdentifier);

        // Strategy 1: Use Reflection (most reliable)
        $file = $this->findViaReflection($className);

        if ($file !== null) {
            return $file;
        }

        // Strategy 2: Check Composer classmap
        $file = $this->findViaClassmap($className);

        if ($file !== null) {
            return $file;
        }

        // Strategy 3: PSR-4 path resolution
        $file = $this->findViaPsr4($className);

        if ($file !== null) {
            return $file;
        }

        // Strategy 4: Glob pattern matching
        $file = $this->findViaGlob($className);

        if ($file !== null) {
            return $file;
        }

        throw new ProductionFileNotFoundException($methodIdentifier, $className);
    }

    /**
     * Check if a production file exists for the given identifier.
     */
    public function productionFileExists(string $methodIdentifier): bool
    {
        try {
            $this->findProductionFile($methodIdentifier);

            return true;
        } catch (ProductionFileNotFoundException) {
            return false;
        }
    }

    /**
     * Extract the class name from a method identifier.
     */
    public function extractClassName(string $methodIdentifier): string
    {
        $parts = explode('::', $methodIdentifier, 2);

        return $parts[0];
    }

    /**
     * Extract the method name from a method identifier.
     */
    public function extractMethodName(string $methodIdentifier): ?string
    {
        $parts = explode('::', $methodIdentifier, 2);

        return $parts[1] ?? null;
    }

    /**
     * Find file via Reflection.
     */
    private function findViaReflection(string $className): ?string
    {
        if (!class_exists($className) && !interface_exists($className) && !trait_exists($className)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($className);
            $file       = $reflection->getFileName();

            if ($file !== false && file_exists($file)) {
                return realpath($file) ?: $file;
            }
        } catch (\ReflectionException) {
            // Class exists but can't be reflected
        }

        return null;
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

        // Common production namespace prefixes
        $prefixes = [
            'App\\'                    => 'app/',
            'Src\\'                    => 'src/',
            'TestFlowLabs\\TestLink\\' => 'src/',
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
            $projectRoot.'/src/'.$relativePath,
            $projectRoot.'/app/'.$relativePath,
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

        // Search in src directory
        $pattern = $projectRoot.'/src/**/'.$shortName.'.php';
        $files   = glob($pattern, GLOB_BRACE);

        if ($files !== false && $files !== []) {
            return realpath($files[0]) ?: $files[0];
        }

        // Try app directory
        $pattern = $projectRoot.'/app/**/'.$shortName.'.php';
        $files   = glob($pattern, GLOB_BRACE);

        if ($files !== false && $files !== []) {
            return realpath($files[0]) ?: $files[0];
        }

        // Try without nested directories
        $pattern = $projectRoot.'/src/'.$shortName.'.php';
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
