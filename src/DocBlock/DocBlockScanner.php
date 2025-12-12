<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

use ReflectionClass;
use ReflectionMethod;
use Composer\Autoload\ClassLoader;

/**
 * Scans source files for @see tags in docblocks.
 *
 * Discovers @see tags in both production and test classes
 * for validation, duplicate detection, and orphan finding.
 */
final class DocBlockScanner
{
    private ?string $projectRoot = null;

    public function __construct(private readonly DocBlockParser $parser = new DocBlockParser()) {}

    /**
     * Set the project root directory for filtering.
     */
    public function setProjectRoot(string $projectRoot): self
    {
        $this->projectRoot = $projectRoot;

        return $this;
    }

    /**
     * Scan all production and test classes for @see tags.
     */
    public function scan(SeeTagRegistry $registry): void
    {
        $this->scanProductionClasses($registry);
        $this->scanTestClasses($registry);
    }

    /**
     * Scan production classes for @see tags pointing to tests.
     */
    public function scanProductionClasses(SeeTagRegistry $registry): void
    {
        $classes = $this->discoverProductionClasses();

        foreach ($classes as $className) {
            $this->scanClass($className, $registry, 'production');
        }
    }

    /**
     * Scan test classes for @see tags pointing to production.
     */
    public function scanTestClasses(SeeTagRegistry $registry): void
    {
        $classes = $this->discoverTestClasses();

        foreach ($classes as $className) {
            $this->scanClass($className, $registry, 'test');
        }
    }

    /**
     * Scan a single class for @see tags in method docblocks.
     *
     * @param  class-string  $className
     * @param  'production'|'test'  $context
     */
    private function scanClass(string $className, SeeTagRegistry $registry, string $context): void
    {
        try {
            $reflection = new ReflectionClass($className);
            $filePath   = $reflection->getFileName();

            if ($filePath === false) {
                return;
            }

            // Scan all methods (public, protected, private)
            foreach ($reflection->getMethods() as $method) {
                // Only scan methods declared in this class, not inherited
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                $this->scanMethodDocBlock($className, $method, $filePath, $registry, $context);
            }
        } catch (\Throwable) {
            // Class might not be loadable, skip it
        }
    }

    /**
     * Scan a method's docblock for @see tags.
     *
     * @param  class-string  $className
     * @param  'production'|'test'  $context
     */
    private function scanMethodDocBlock(
        string $className,
        ReflectionMethod $method,
        string $filePath,
        SeeTagRegistry $registry,
        string $context
    ): void {
        $docComment = $method->getDocComment();

        if ($docComment === false) {
            return;
        }

        $references       = $this->parser->extractSeeReferences($docComment);
        $methodIdentifier = $className.'::'.$method->getName();

        foreach ($references as $reference) {
            $entry = new SeeTagEntry(
                reference: $reference,
                filePath: $filePath,
                line: $method->getStartLine() ?: 0,
                context: $context,
                methodName: $method->getName(),
                className: $className,
            );

            if ($context === 'production') {
                $registry->registerProductionSee($methodIdentifier, $entry);
            } else {
                $registry->registerTestSee($methodIdentifier, $entry);
            }
        }
    }

    /**
     * Discover all production classes from Composer's classmap.
     *
     * @return list<class-string>
     */
    private function discoverProductionClasses(): array
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
     * Discover all test classes from Composer's classmap.
     *
     * @return list<class-string>
     */
    private function discoverTestClasses(): array
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
     * Check if a file is a production class.
     */
    private function isProductionClass(string $file, string $projectRoot): bool
    {
        $realFile = realpath($file);
        $realRoot = realpath($projectRoot);

        if ($realFile === false || $realRoot === false) {
            return false;
        }

        if (!str_starts_with($realFile, $realRoot)) {
            return false;
        }

        if (str_contains($realFile, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
            return false;
        }

        if (str_contains($realFile, DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR)) {
            return false;
        }

        return str_contains($realFile, DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR)
            || str_contains($realFile, DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR);
    }

    /**
     * Check if a file is a test class.
     */
    private function isTestClass(string $file, string $projectRoot): bool
    {
        $realFile = realpath($file);
        $realRoot = realpath($projectRoot);

        if ($realFile === false || $realRoot === false) {
            return false;
        }

        if (!str_starts_with($realFile, $realRoot)) {
            return false;
        }

        if (str_contains($realFile, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
            return false;
        }

        return str_contains($realFile, DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR);
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

        $autoloadPath = $this->findAutoloadPath();

        if ($autoloadPath === null || !file_exists($autoloadPath)) {
            return null;
        }

        require_once $autoloadPath;

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
     * Detect project root from current working directory.
     */
    private function detectProjectRoot(): string
    {
        return getcwd() ?: __DIR__;
    }
}
