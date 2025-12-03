<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Placeholder;

use PhpParser\Node;
use ReflectionClass;
use PhpParser\Parser;
use ReflectionMethod;
use ReflectionAttribute;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Composer\Autoload\ClassLoader;
use TestFlowLabs\TestingAttributes\Links;
use TestFlowLabs\TestingAttributes\TestedBy;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

/**
 * Scans production and test files for placeholder identifiers.
 *
 * Finds placeholders like @A, @user-create in:
 * - Production: #[TestedBy('@A')]
 * - Tests (Pest): linksAndCovers('@A'), links('@A')
 * - Tests (PHPUnit): #[LinksAndCovers('@A')], #[Links('@A')]
 */
final class PlaceholderScanner
{
    private readonly Parser $parser;
    private readonly NodeFinder $nodeFinder;
    private ?string $projectRoot = null;

    public function __construct()
    {
        $this->parser     = (new ParserFactory())->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder();
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
     * Scan all production and test files for placeholders.
     */
    public function scan(PlaceholderRegistry $registry): void
    {
        $this->scanProductionClasses($registry);
        $this->scanTestFiles($registry);
    }

    /**
     * Scan production classes for #[TestedBy('@X')] placeholders.
     */
    public function scanProductionClasses(PlaceholderRegistry $registry): void
    {
        $classes = $this->discoverProductionClasses();

        foreach ($classes as $className) {
            $this->scanProductionClass($className, $registry);
        }
    }

    /**
     * Scan a single production class for placeholder TestedBy attributes.
     *
     * @param  class-string  $className
     */
    public function scanProductionClass(string $className, PlaceholderRegistry $registry): void
    {
        try {
            $reflection = new ReflectionClass($className);
            $filePath   = $reflection->getFileName();

            if ($filePath === false) {
                return;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->scanTestedByPlaceholders($className, $method, $filePath, $registry);
            }
        } catch (\Throwable) {
            // Class might not be loadable, skip it
        }
    }

    /**
     * Scan test files for placeholder linksAndCovers/links.
     */
    public function scanTestFiles(PlaceholderRegistry $registry): void
    {
        $testFiles = $this->discoverTestFiles();

        foreach ($testFiles as $filePath) {
            if ($this->isPestFile($filePath)) {
                $this->scanPestFile($filePath, $registry);
            }
        }

        // Also scan PHPUnit test classes via reflection
        $testClasses = $this->discoverTestClasses();

        foreach ($testClasses as $className) {
            $this->scanPhpUnitClass($className, $registry);
        }
    }

    /**
     * Scan method for placeholder #[TestedBy] attributes.
     *
     * @param  class-string  $className
     */
    private function scanTestedByPlaceholders(
        string $className,
        ReflectionMethod $method,
        string $filePath,
        PlaceholderRegistry $registry,
    ): void {
        $testedByAttrs = $method->getAttributes(TestedBy::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($testedByAttrs as $attribute) {
            $instance  = $attribute->newInstance();
            $testClass = $instance->testClass;

            // Only process placeholders (values starting with @)
            if (!PlaceholderRegistry::isPlaceholder($testClass)) {
                continue;
            }

            $line = $method->getStartLine();

            if ($line === false) {
                continue;
            }

            $registry->registerProductionPlaceholder(
                placeholder: $testClass,
                className: $className,
                methodName: $method->getName(),
                filePath: $filePath,
                line: $line,
            );
        }
    }

    /**
     * Scan a Pest file for placeholder linksAndCovers/links calls.
     */
    private function scanPestFile(string $filePath, PlaceholderRegistry $registry): void
    {
        $code = file_get_contents($filePath);

        if ($code === false) {
            return;
        }

        $ast = $this->parse($code);

        if ($ast === null) {
            return;
        }

        // Derive namespace from file path
        $namespace = $this->deriveNamespaceFromPath($filePath);

        // Find all test/it calls and their method chains
        $this->findPestPlaceholders($ast, $filePath, $namespace, $registry);
    }

    /**
     * Scan a PHPUnit test class for placeholder attributes.
     *
     * @param  class-string  $className
     */
    private function scanPhpUnitClass(string $className, PlaceholderRegistry $registry): void
    {
        try {
            $reflection = new ReflectionClass($className);
            $filePath   = $reflection->getFileName();

            if ($filePath === false) {
                return;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->scanPhpUnitPlaceholders($className, $method, $filePath, $registry);
            }
        } catch (\Throwable) {
            // Class might not be loadable, skip it
        }
    }

    /**
     * Scan PHPUnit method for placeholder attributes.
     *
     * @param  class-string  $className
     */
    private function scanPhpUnitPlaceholders(
        string $className,
        ReflectionMethod $method,
        string $filePath,
        PlaceholderRegistry $registry,
    ): void {
        // Check LinksAndCovers attributes
        $linksAndCoversAttrs = $method->getAttributes(LinksAndCovers::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($linksAndCoversAttrs as $attribute) {
            $instance    = $attribute->newInstance();
            $targetClass = $instance->class;

            // Only process placeholders
            if (!PlaceholderRegistry::isPlaceholder($targetClass)) {
                continue;
            }

            $testIdentifier = $className.'::'.$method->getName();
            $line           = $method->getStartLine();

            if ($line === false) {
                continue;
            }

            $registry->registerTestPlaceholder(
                placeholder: $targetClass,
                testIdentifier: $testIdentifier,
                filePath: $filePath,
                line: $line,
                framework: 'phpunit',
            );
        }

        // Check Links attributes
        $linksAttrs = $method->getAttributes(Links::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($linksAttrs as $attribute) {
            $instance    = $attribute->newInstance();
            $targetClass = $instance->class;

            // Only process placeholders
            if (!PlaceholderRegistry::isPlaceholder($targetClass)) {
                continue;
            }

            $testIdentifier = $className.'::'.$method->getName();
            $line           = $method->getStartLine();

            if ($line === false) {
                continue;
            }

            $registry->registerTestPlaceholder(
                placeholder: $targetClass,
                testIdentifier: $testIdentifier,
                filePath: $filePath,
                line: $line,
                framework: 'phpunit',
            );
        }
    }

    /**
     * Find placeholder values in Pest test() and it() chains.
     *
     * @param  array<Node>  $ast
     * @param  list<string>  $describePath
     */
    private function findPestPlaceholders(
        array $ast,
        string $filePath,
        string $namespace,
        PlaceholderRegistry $registry,
        array $describePath = [],
    ): void {
        // Find describe blocks and recurse
        $describeCalls = $this->nodeFinder->find($ast, fn (Node $node): bool => $node instanceof Node\Expr\FuncCall
            && $this->getFunctionName($node) === 'describe');

        foreach ($describeCalls as $describe) {
            if (!$describe instanceof Node\Expr\FuncCall) {
                continue;
            }

            $describeName = $this->extractStringArg($describe);

            if ($describeName === null) {
                continue;
            }

            $body = $this->getClosureBody($describe);

            if ($body !== null) {
                $this->findPestPlaceholders($body, $filePath, $namespace, $registry, [...$describePath, $describeName]);
            }
        }

        // Find test() and it() calls
        $testCalls = $this->findDirectTestCalls($ast);

        foreach ($testCalls as $call) {
            $testName = $this->extractStringArg($call);

            if ($testName === null) {
                continue;
            }

            // Build full test identifier
            $fullTestName = $describePath !== []
                ? implode(' > ', $describePath).' > '.$testName
                : $testName;

            $testIdentifier = $namespace.'::'.$fullTestName;

            // Find linksAndCovers/links method calls with placeholders
            $this->findPlaceholderMethodCalls($call, $testIdentifier, $filePath, $registry);
        }
    }

    /**
     * Find linksAndCovers/links calls with placeholder arguments.
     */
    private function findPlaceholderMethodCalls(
        Node\Expr\FuncCall $testCall,
        string $testIdentifier,
        string $filePath,
        PlaceholderRegistry $registry,
    ): void {
        $parent = $testCall->getAttribute('parent');

        while ($parent instanceof Node\Expr\MethodCall) {
            $methodName = $parent->name;

            if ($methodName instanceof Node\Identifier
                && in_array($methodName->toString(), ['linksAndCovers', 'links'], true)
            ) {
                foreach ($parent->args as $arg) {
                    if (!$arg instanceof Node\Arg) {
                        continue;
                    }

                    $placeholder = $this->extractPlaceholderFromArg($arg);

                    if ($placeholder !== null && PlaceholderRegistry::isPlaceholder($placeholder)) {
                        $registry->registerTestPlaceholder(
                            placeholder: $placeholder,
                            testIdentifier: $testIdentifier,
                            filePath: $filePath,
                            line: $parent->getStartLine(),
                            framework: 'pest',
                        );
                    }
                }
            }

            $parent = $parent->getAttribute('parent');
        }
    }

    /**
     * Extract placeholder value from a method argument.
     */
    private function extractPlaceholderFromArg(Node\Arg $arg): ?string
    {
        // Direct string: '@A'
        if ($arg->value instanceof Node\Scalar\String_) {
            return $arg->value->value;
        }

        return null;
    }

    /**
     * Find direct test() or it() calls (not recursive).
     *
     * @param  array<Node>  $nodes
     *
     * @return list<Node\Expr\FuncCall>
     */
    private function findDirectTestCalls(array $nodes): array
    {
        $calls = [];

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Expression) {
                $funcCall = $this->extractRootTestCall($node->expr);

                if ($funcCall !== null) {
                    $calls[] = $funcCall;
                }
            }
        }

        return $calls;
    }

    /**
     * Extract root test/it FuncCall from an expression.
     */
    private function extractRootTestCall(Node\Expr $expr): ?Node\Expr\FuncCall
    {
        // Direct test() or it() call
        if ($expr instanceof Node\Expr\FuncCall) {
            $funcName = $this->getFunctionName($expr);

            if (in_array($funcName, ['test', 'it'], true)) {
                return $expr;
            }
        }

        // Method chain starting with test() or it()
        if ($expr instanceof Node\Expr\MethodCall) {
            $root = $this->getRootFuncCall($expr);

            if ($root instanceof Node\Expr\FuncCall) {
                $funcName = $this->getFunctionName($root);

                if (in_array($funcName, ['test', 'it'], true)) {
                    return $root;
                }
            }
        }

        return null;
    }

    /**
     * Get the root FuncCall from a method chain.
     */
    private function getRootFuncCall(Node\Expr\MethodCall $call): ?Node\Expr\FuncCall
    {
        $current = $call->var;

        while ($current instanceof Node\Expr\MethodCall) {
            $current = $current->var;
        }

        return $current instanceof Node\Expr\FuncCall ? $current : null;
    }

    /**
     * Parse code into AST with parent connections.
     *
     * @return array<Node>|null
     */
    private function parse(string $code): ?array
    {
        try {
            $ast = $this->parser->parse($code);

            if ($ast === null) {
                return null;
            }

            $traverser = new NodeTraverser();
            $traverser->addVisitor(new ParentConnectingVisitor());

            return $traverser->traverse($ast);
        } catch (\PhpParser\Error) {
            return null;
        }
    }

    /**
     * Get function name from a FuncCall node.
     */
    private function getFunctionName(Node\Expr\FuncCall $call): ?string
    {
        if ($call->name instanceof Node\Name) {
            return $call->name->toString();
        }

        return null;
    }

    /**
     * Extract string argument from a function call.
     */
    private function extractStringArg(Node\Expr\FuncCall $call): ?string
    {
        $firstArg = $call->args[0] ?? null;

        if (!$firstArg instanceof Node\Arg) {
            return null;
        }

        if ($firstArg->value instanceof Node\Scalar\String_) {
            return $firstArg->value->value;
        }

        return null;
    }

    /**
     * Get the closure body from a function call.
     *
     * @return array<Node>|null
     */
    private function getClosureBody(Node\Expr\FuncCall $call): ?array
    {
        foreach ($call->args as $arg) {
            if (!$arg instanceof Node\Arg) {
                continue;
            }

            if ($arg->value instanceof Node\Expr\Closure) {
                return $arg->value->stmts;
            }

            if ($arg->value instanceof Node\Expr\ArrowFunction) {
                return [$arg->value->expr];
            }
        }

        return null;
    }

    /**
     * Derive namespace from file path (e.g., tests/Unit/UserTest.php -> Tests\Unit\UserTest).
     */
    private function deriveNamespaceFromPath(string $filePath): string
    {
        $projectRoot  = $this->projectRoot ?? $this->detectProjectRoot();
        $relativePath = str_replace($projectRoot.'/', '', $filePath);

        // Remove .php extension
        $relativePath = preg_replace('/\.php$/', '', $relativePath);

        // Convert path to namespace
        $namespace = str_replace('/', '\\', $relativePath ?? '');

        // Capitalize first letter of each segment
        $parts = explode('\\', $namespace);
        $parts = array_map('ucfirst', $parts);

        return implode('\\', $parts);
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
     * Discover all test files.
     *
     * @return list<string>
     */
    private function discoverTestFiles(): array
    {
        $projectRoot = $this->projectRoot ?? $this->detectProjectRoot();
        $testsDir    = $projectRoot.'/tests';

        if (!is_dir($testsDir)) {
            return [];
        }

        $files    = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($testsDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    /**
     * Check if a file is a Pest test file.
     */
    private function isPestFile(string $filePath): bool
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return false;
        }

        return preg_match('/\b(test|it)\s*\(/', $content) === 1;
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
