<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Parser;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use TestFlowLabs\TestLink\Sync\Parser\ParsedTestCase;
use TestFlowLabs\TestLink\Contract\TestParserInterface;

/**
 * Parses PHPUnit test classes to find test methods and their attributes.
 */
final class PhpUnitTestParser implements TestParserInterface
{
    private readonly Parser $parser;
    private readonly NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->parser     = (new ParserFactory())->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder();
    }

    /**
     * {@inheritDoc}
     */
    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $code = file_get_contents($filePath);

        if ($code === false) {
            return [];
        }

        return $this->findAllTests($code);
    }

    /**
     * {@inheritDoc}
     */
    public function findTestByName(string $code, string $testName): ?ParsedTestCase
    {
        $tests = $this->findAllTests($code);

        foreach ($tests as $test) {
            if ($test->name === $testName) {
                return $test;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAllTests(string $code): array
    {
        $ast = $this->parse($code);

        if ($ast === null) {
            return [];
        }

        $tests     = [];
        $className = $this->findClassName($ast);

        // Find all test methods
        $methods = $this->findTestMethods($ast);

        foreach ($methods as $method) {
            $tests[] = new ParsedTestCase(
                name: $method->name->toString(),
                type: ParsedTestCase::TYPE_PHPUNIT,
                startLine: $method->getStartLine(),
                endLine: $method->getEndLine(),
                existingCoversMethod: $this->findExistingLinkAttributes($method),
                describePath: $className !== null ? [$className] : [],
            );
        }

        return $tests;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            return false;
        }

        // Check for PHPUnit test class indicators
        return preg_match('/extends\s+TestCase\b/', $content) === 1
            || preg_match('/use\s+PHPUnit\\\\Framework\\\\TestCase/', $content) === 1;
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
     * Find test methods in the AST.
     *
     * A method is considered a test if:
     * - It starts with 'test' prefix
     * - It has #[Test] attribute
     * - It has @test annotation (docblock)
     *
     * @param  array<Node>  $ast
     *
     * @return list<Node\Stmt\ClassMethod>
     */
    private function findTestMethods(array $ast): array
    {
        $methods = [];

        /** @var list<Node\Stmt\ClassMethod> $classMethods */
        $classMethods = $this->nodeFinder->findInstanceOf($ast, Node\Stmt\ClassMethod::class);

        foreach ($classMethods as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            // Check for test* prefix
            if (str_starts_with($method->name->toString(), 'test')) {
                $methods[] = $method;

                continue;
            }

            // Check for #[Test] attribute
            if ($this->hasTestAttribute($method)) {
                $methods[] = $method;

                continue;
            }

            // Check for @test docblock annotation
            if ($this->hasTestAnnotation($method)) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    /**
     * Check if method has #[Test] attribute.
     */
    private function hasTestAttribute(Node\Stmt\ClassMethod $method): bool
    {
        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $attr->name->toString();

                if ($name === 'Test' || str_ends_with($name, '\\Test')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if method has @test docblock annotation.
     */
    private function hasTestAnnotation(Node\Stmt\ClassMethod $method): bool
    {
        $docComment = $method->getDocComment();

        if (!$docComment instanceof \PhpParser\Comment\Doc) {
            return false;
        }

        return str_contains($docComment->getText(), '@test');
    }

    /**
     * Find existing #[Links] and #[LinksAndCovers] attributes on a method.
     *
     * @return list<string> Method identifiers from attributes
     */
    private function findExistingLinkAttributes(Node\Stmt\ClassMethod $method): array
    {
        $methods = [];

        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $attr->name->toString();

                // Check for our link attributes
                if (!$this->isLinkAttribute($name)) {
                    continue;
                }

                $methodIdentifier = $this->extractMethodIdentifierFromAttribute($attr);

                if ($methodIdentifier !== null) {
                    $methods[] = $methodIdentifier;
                }
            }
        }

        return $methods;
    }

    /**
     * Check if attribute name is a link attribute.
     */
    private function isLinkAttribute(string $name): bool
    {
        $linkAttributes = [
            'Links',
            'LinksAndCovers',
            \TestFlowLabs\TestingAttributes\Links::class,
            \TestFlowLabs\TestingAttributes\LinksAndCovers::class,
        ];

        foreach ($linkAttributes as $linkAttr) {
            if ($name === $linkAttr || str_ends_with($name, '\\'.$linkAttr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract method identifier from a link attribute.
     *
     * Handles:
     * - #[Links(UserService::class, 'create')]
     * - #[LinksAndCovers(UserService::class, 'create')]
     * - #[Links(UserService::class)] (class-level)
     */
    private function extractMethodIdentifierFromAttribute(Node\Attribute $attr): ?string
    {
        if (count($attr->args) < 1) {
            return null;
        }

        $classArg  = $attr->args[0];
        $methodArg = $attr->args[1] ?? null;

        // Extract class name
        $className = null;

        if ($classArg->value instanceof Node\Expr\ClassConstFetch
            && $classArg->value->name instanceof Node\Identifier
            && $classArg->value->name->toString() === 'class'
            && $classArg->value->class instanceof Node\Name
        ) {
            $className = $classArg->value->class->toString();
        } elseif ($classArg->value instanceof Node\Scalar\String_) {
            $className = $classArg->value->value;
        }

        if ($className === null) {
            return null;
        }

        // Extract method name (optional)
        $methodName = null;

        if ($methodArg instanceof Node\Arg && $methodArg->value instanceof Node\Scalar\String_) {
            $methodName = $methodArg->value->value;
        }

        return $methodName !== null
            ? $className.'::'.$methodName
            : $className;
    }

    /**
     * Find the class name in the AST.
     *
     * @param  array<Node>  $ast
     */
    private function findClassName(array $ast): ?string
    {
        /** @var Node\Stmt\Class_|null $class */
        $class = $this->nodeFinder->findFirstInstanceOf($ast, Node\Stmt\Class_::class);

        if ($class === null || $class->name === null) {
            return null;
        }

        // Find namespace
        /** @var Node\Stmt\Namespace_|null $namespace */
        $namespace = $this->nodeFinder->findFirstInstanceOf($ast, Node\Stmt\Namespace_::class);

        $namespaceName = $namespace instanceof Node\Stmt\Namespace_ && $namespace->name instanceof \PhpParser\Node\Name
            ? $namespace->name->toString().'\\'
            : '';

        return $namespaceName.$class->name->toString();
    }
}
