<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Scanner;

use ReflectionClass;
use ReflectionMethod;
use ReflectionAttribute;
use Composer\Autoload\ClassLoader;
use TestFlowLabs\TestingAttributes\Links;
use TestFlowLabs\TestLink\Attribute\TestedBy;
use TestFlowLabs\TestingAttributes\LinksAndCovers;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * Scans classes for test link attributes.
 *
 * Supports bidirectional scanning:
 * - Test classes: #[LinksAndCovers] and #[Links] attributes
 * - Production classes: #[TestedBy] attributes
 *
 * Discovers classes from Composer's classmap and registers
 * links between tests and production methods.
 */
final class AttributeScanner
{
    private ?string $projectRoot = null;

    /**
     * Set the project root directory for filtering.
     */
    public function setProjectRoot(string $projectRoot): self
    {
        $this->projectRoot = $projectRoot;

        return $this;
    }

    /**
     * Scan a single test class for #[LinksAndCovers] and #[Links] attributes.
     *
     * @param  class-string  $className
     */
    public function scanClass(string $className, TestLinkRegistry $registry): void
    {
        try {
            $reflection = new ReflectionClass($className);

            // Scan method-level attributes
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->scanMethodAttributes($className, $method, $registry);
            }
        } catch (\Throwable) {
            // Class might not be loadable, skip it
        }
    }

    /**
     * Scan multiple classes for #[LinksAndCovers] and #[Links] attributes.
     *
     * @param  array<class-string>  $classNames
     */
    public function scanClasses(array $classNames, TestLinkRegistry $registry): void
    {
        foreach ($classNames as $className) {
            $this->scanClass($className, $registry);
        }
    }

    /**
     * Discover all test classes from Composer's classmap.
     *
     * @return list<class-string>
     */
    public function discoverClasses(): array
    {
        $loader = $this->getComposerLoader();

        if (!$loader instanceof ClassLoader) {
            return [];
        }

        $projectRoot = $this->projectRoot ?? $this->detectProjectRoot();

        /** @var list<class-string> $classes */
        $classes = [];

        /** @var array<class-string, string> $classMap */
        $classMap = $loader->getClassMap();

        foreach ($classMap as $class => $file) {
            if ($this->isTestClass($file, $projectRoot)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * Discover test classes and scan them all.
     */
    public function discoverAndScan(TestLinkRegistry $registry): void
    {
        $classes = $this->discoverClasses();
        $this->scanClasses($classes, $registry);
    }

    /**
     * Scan a single production class for #[TestedBy] attributes.
     *
     * @param  class-string  $className
     */
    public function scanProductionClass(string $className, TestLinkRegistry $registry): void
    {
        try {
            $reflection = new ReflectionClass($className);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->scanTestedByAttributes($className, $method, $registry);
            }
        } catch (\Throwable) {
            // Class might not be loadable, skip it
        }
    }

    /**
     * Scan multiple production classes for #[TestedBy] attributes.
     *
     * @param  array<class-string>  $classNames
     */
    public function scanProductionClasses(array $classNames, TestLinkRegistry $registry): void
    {
        foreach ($classNames as $className) {
            $this->scanProductionClass($className, $registry);
        }
    }

    /**
     * Discover all production classes from Composer's classmap.
     *
     * @return list<class-string>
     */
    public function discoverProductionClasses(): array
    {
        $loader = $this->getComposerLoader();

        if (!$loader instanceof ClassLoader) {
            return [];
        }

        $projectRoot = $this->projectRoot ?? $this->detectProjectRoot();

        /** @var list<class-string> $classes */
        $classes = [];

        /** @var array<class-string, string> $classMap */
        $classMap = $loader->getClassMap();

        foreach ($classMap as $class => $file) {
            if ($this->isProductionClass($file, $projectRoot)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * Discover and scan both test and production classes.
     */
    public function discoverAndScanAll(TestLinkRegistry $registry): void
    {
        // Scan test classes for LinksAndCovers/Links
        $testClasses = $this->discoverClasses();
        $this->scanClasses($testClasses, $registry);

        // Scan production classes for TestedBy
        $productionClasses = $this->discoverProductionClasses();
        $this->scanProductionClasses($productionClasses, $registry);
    }

    /**
     * Scan method-level #[TestedBy] attributes on production code.
     *
     * @param  class-string  $className
     */
    private function scanTestedByAttributes(
        string $className,
        ReflectionMethod $method,
        TestLinkRegistry $registry,
    ): void {
        $testedByAttrs = $method->getAttributes(TestedBy::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($testedByAttrs as $attribute) {
            $instance         = $attribute->newInstance();
            $methodIdentifier = "{$className}::{$method->getName()}";
            $testIdentifier   = $instance->getTestIdentifier();
            $registry->registerTestedBy($methodIdentifier, $testIdentifier);
        }
    }

    /**
     * Scan method-level #[LinksAndCovers] and #[Links] attributes.
     *
     * @param  class-string  $className
     */
    private function scanMethodAttributes(
        string $className,
        ReflectionMethod $method,
        TestLinkRegistry $registry,
    ): void {
        // Scan for LinksAndCovers attributes
        $linksAndCoversAttrs = $method->getAttributes(LinksAndCovers::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($linksAndCoversAttrs as $attribute) {
            $instance         = $attribute->newInstance();
            $testIdentifier   = $this->buildTestIdentifier($className, $method->getName());
            $methodIdentifier = $instance->getMethodIdentifier();
            $registry->registerLink($testIdentifier, $methodIdentifier);
        }

        // Scan for Links attributes
        $linksAttrs = $method->getAttributes(Links::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($linksAttrs as $attribute) {
            $instance         = $attribute->newInstance();
            $testIdentifier   = $this->buildTestIdentifier($className, $method->getName());
            $methodIdentifier = $instance->getMethodIdentifier();
            $registry->registerLink($testIdentifier, $methodIdentifier);
        }
    }

    /**
     * Build a test identifier from class and method name.
     *
     * @param  class-string  $className
     */
    private function buildTestIdentifier(string $className, string $methodName): string
    {
        return "{$className}::{$methodName}";
    }

    /**
     * Check if a file is a test class (in tests directory, not vendor).
     */
    private function isTestClass(string $file, string $projectRoot): bool
    {
        $realFile = realpath($file);
        $realRoot = realpath($projectRoot);

        if ($realFile === false || $realRoot === false) {
            return false;
        }

        // Must be inside project root
        if (!str_starts_with($realFile, $realRoot)) {
            return false;
        }

        // Must not be in vendor directory
        if (str_contains($realFile, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
            return false;
        }

        // Must be in tests directory
        return str_contains($realFile, DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR);
    }

    /**
     * Check if a file is a production class (in src/app directory, not vendor or tests).
     */
    private function isProductionClass(string $file, string $projectRoot): bool
    {
        $realFile = realpath($file);
        $realRoot = realpath($projectRoot);

        if ($realFile === false || $realRoot === false) {
            return false;
        }

        // Must be inside project root
        if (!str_starts_with($realFile, $realRoot)) {
            return false;
        }

        // Must not be in vendor directory
        if (str_contains($realFile, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
            return false;
        }

        // Must not be in tests directory
        if (str_contains($realFile, DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR)) {
            return false;
        }

        // Must be in src or app directory
        return str_contains($realFile, DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR)
            || str_contains($realFile, DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR);
    }

    /**
     * Get the Composer autoloader instance.
     */
    private function getComposerLoader(): ?ClassLoader
    {
        // Try to get already-registered autoloaders
        foreach (spl_autoload_functions() as $autoloader) {
            if (is_array($autoloader) && $autoloader[0] instanceof ClassLoader) {
                return $autoloader[0];
            }
        }

        // Fallback: Try to require vendor/autoload.php
        $autoloadPath = $this->findAutoloadPath();

        if ($autoloadPath === null || !file_exists($autoloadPath)) {
            return null;
        }

        require_once $autoloadPath;

        // Try again to find the loader
        foreach (spl_autoload_functions() as $autoloader) {
            if (is_array($autoloader) && $autoloader[0] instanceof ClassLoader) {
                return $autoloader[0];
            }
        }

        return null;
    }

    /**
     * Find the path to vendor/autoload.php.
     */
    private function findAutoloadPath(): ?string
    {
        $projectRoot  = $this->projectRoot ?? $this->detectProjectRoot();
        $autoloadPath = $projectRoot.'/vendor/autoload.php';

        if (file_exists($autoloadPath)) {
            return $autoloadPath;
        }

        return null;
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
