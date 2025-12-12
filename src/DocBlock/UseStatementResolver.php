<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\DocBlock;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * Resolves short class names to FQCNs using PHP use statements.
 *
 * Parses PHP files to extract use statements and provides
 * resolution of short class names to their fully qualified forms.
 */
final class UseStatementResolver
{
    private readonly Parser $parser;
    private readonly NodeFinder $nodeFinder;

    /** @var array<string, array{uses: array<string, string>, namespace: string|null}> cache */
    private array $fileCache = [];

    public function __construct()
    {
        $this->parser     = (new ParserFactory())->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder();
    }

    /**
     * Resolve a short class reference to FQCN.
     *
     * @param  string  $reference  Short reference like "UserTest::testMethod" or "UserTest"
     * @param  string  $filePath  File where the reference was found
     *
     * @return array{fqcn: string|null, error: string|null}
     */
    public function resolve(string $reference, string $filePath): array
    {
        // Already FQCN
        if (str_starts_with($reference, '\\')) {
            return ['fqcn' => $reference, 'error' => null];
        }

        // Parse file to get use statements
        $fileInfo = $this->parseFile($filePath);

        if ($fileInfo === null) {
            return ['fqcn' => null, 'error' => 'Could not parse file'];
        }

        // Split reference into class and method parts
        [$className, $methodName] = $this->splitReference($reference);

        if ($className === null) {
            // Method-only reference (e.g., "testCreate") - cannot resolve
            return ['fqcn' => null, 'error' => 'Method-only reference cannot be resolved'];
        }

        // Try to resolve the class name
        $resolvedClass = $this->resolveClassName(
            $className,
            $fileInfo['uses'],
            $fileInfo['namespace']
        );

        if ($resolvedClass === null) {
            return [
                'fqcn'  => null,
                'error' => "Could not resolve '{$className}' - not found in use statements",
            ];
        }

        // Build full FQCN reference
        $fqcn = '\\'.ltrim($resolvedClass, '\\');

        if ($methodName !== null) {
            $fqcn .= '::'.$methodName;
        }

        return ['fqcn' => $fqcn, 'error' => null];
    }

    /**
     * Split reference into class and method parts.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function splitReference(string $reference): array
    {
        if (str_contains($reference, '::')) {
            $parts = explode('::', $reference, 2);

            return [$parts[0], rtrim($parts[1], '()')];
        }

        // Check if it looks like a class name (starts with uppercase)
        if (preg_match('/^[A-Z]/', $reference) === 1) {
            return [$reference, null];
        }

        // Looks like method-only reference
        return [null, $reference];
    }

    /**
     * Resolve a class name using use statements.
     *
     * @param  array<string, string>  $uses  Mapping of short name => FQCN
     */
    private function resolveClassName(
        string $className,
        array $uses,
        ?string $namespace,
    ): ?string {
        // Check direct use statement match
        if (isset($uses[$className])) {
            return $uses[$className];
        }

        // Check if class is in same namespace
        if ($namespace !== null) {
            $sameNamespaceFqcn = $namespace.'\\'.$className;

            if (class_exists($sameNamespaceFqcn)) {
                return $sameNamespaceFqcn;
            }
        }

        // Check if it's a global class
        if (class_exists($className)) {
            return $className;
        }

        return null;
    }

    /**
     * Parse a file and extract use statements and namespace.
     *
     * @return array{uses: array<string, string>, namespace: string|null}|null
     */
    private function parseFile(string $filePath): ?array
    {
        // Check cache
        if (isset($this->fileCache[$filePath])) {
            return $this->fileCache[$filePath];
        }

        if (!file_exists($filePath)) {
            return null;
        }

        $code = file_get_contents($filePath);

        if ($code === false) {
            return null;
        }

        try {
            $ast = $this->parser->parse($code);

            if ($ast === null) {
                return null;
            }

            $namespace = $this->extractNamespace($ast);
            $uses      = $this->extractUseStatements($ast);

            $result = [
                'uses'      => $uses,
                'namespace' => $namespace,
            ];

            $this->fileCache[$filePath] = $result;

            return $result;
        } catch (\PhpParser\Error) {
            return null;
        }
    }

    /**
     * Extract namespace from AST.
     *
     * @param  array<Node>  $ast
     */
    private function extractNamespace(array $ast): ?string
    {
        $namespace = $this->nodeFinder->findFirstInstanceOf($ast, Node\Stmt\Namespace_::class);

        if ($namespace instanceof Node\Stmt\Namespace_ && $namespace->name instanceof \PhpParser\Node\Name) {
            return $namespace->name->toString();
        }

        return null;
    }

    /**
     * Extract use statements from AST.
     *
     * Handles:
     * - Simple: use Foo\Bar;
     * - Aliased: use Foo\Bar as Baz;
     * - Grouped: use Foo\{Bar, Baz};
     *
     * @param  array<Node>  $ast
     *
     * @return array<string, string> alias/short name => FQCN
     */
    private function extractUseStatements(array $ast): array
    {
        $uses = [];

        /** @var list<Node\Stmt\Use_> $useNodes */
        $useNodes = $this->nodeFinder->findInstanceOf($ast, Node\Stmt\Use_::class);

        foreach ($useNodes as $useNode) {
            // Only process class imports (not function or const)
            if ($useNode->type !== Node\Stmt\Use_::TYPE_NORMAL) {
                continue;
            }

            foreach ($useNode->uses as $use) {
                $fqcn  = $use->name->toString();
                $alias = $use->alias?->toString() ?? $use->name->getLast();

                $uses[$alias] = $fqcn;
            }
        }

        // Handle grouped use statements: use Foo\{Bar, Baz};
        /** @var list<Node\Stmt\GroupUse> $groupUseNodes */
        $groupUseNodes = $this->nodeFinder->findInstanceOf($ast, Node\Stmt\GroupUse::class);

        foreach ($groupUseNodes as $groupUse) {
            $prefix = $groupUse->prefix->toString();

            foreach ($groupUse->uses as $use) {
                // Only process class imports
                if ($use->type !== Node\Stmt\Use_::TYPE_NORMAL
                    && $use->type !== Node\Stmt\Use_::TYPE_UNKNOWN
                ) {
                    continue;
                }

                $fqcn  = $prefix.'\\'.$use->name->toString();
                $alias = $use->alias?->toString() ?? $use->name->getLast();

                $uses[$alias] = $fqcn;
            }
        }

        return $uses;
    }

    /**
     * Clear the file cache.
     */
    public function clearCache(): void
    {
        $this->fileCache = [];
    }
}
