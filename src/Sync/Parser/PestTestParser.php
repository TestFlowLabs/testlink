<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Parser;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use TestFlowLabs\TestLink\Contract\TestParserInterface;

/**
 * Parses Pest test files to find test() and it() calls.
 */
final class PestTestParser implements TestParserInterface
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
    public function supports(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            return false;
        }

        // Check for Pest test() or it() function calls
        return preg_match('/\b(test|it)\s*\(/', $content) === 1;
    }

    /**
     * Find a test case by name in the given code.
     */
    public function findTestByName(string $code, string $testName): ?ParsedTestCase
    {
        $ast = $this->parse($code);

        if ($ast === null) {
            return null;
        }

        // Handle describe block paths: "describe1 > describe2 > test name"
        if (str_contains($testName, ' > ')) {
            return $this->findInDescribeBlocks($ast, $testName);
        }

        return $this->findSimpleTest($ast, $testName);
    }

    /**
     * Find all test cases in the given code.
     *
     * @return list<ParsedTestCase>
     */
    public function findAllTests(string $code): array
    {
        $ast = $this->parse($code);

        if ($ast === null) {
            return [];
        }

        $tests = [];

        // Find top-level test() and it() calls
        $testCalls = $this->findTestCalls($ast);

        foreach ($testCalls as $call) {
            $testName = $this->extractTestName($call);

            if ($testName === null) {
                continue;
            }

            $tests[] = new ParsedTestCase(
                name: $testName,
                type: ParsedTestCase::TYPE_PEST,
                startLine: $call->getStartLine(),
                endLine: $this->findEndLine($call),
                existingCoversMethod: $this->findExistingCoversMethod($call),
            );
        }

        // Find tests inside describe blocks
        $describeTests = $this->findTestsInDescribeBlocks($ast);

        return [...$tests, ...$describeTests];
    }

    /**
     * Parse code into AST with parent connections and resolved names.
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

            // Resolve names (converts short class names to FQN) and connect parent nodes
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());
            $traverser->addVisitor(new ParentConnectingVisitor());

            return $traverser->traverse($ast);
        } catch (\PhpParser\Error) {
            return null;
        }
    }

    /**
     * Find a simple test (not in describe block).
     *
     * @param  array<Node>  $ast
     */
    private function findSimpleTest(array $ast, string $testName): ?ParsedTestCase
    {
        $testCalls = $this->findTestCalls($ast);

        foreach ($testCalls as $call) {
            $name = $this->extractTestName($call);

            if ($name === $testName) {
                return new ParsedTestCase(
                    name: $testName,
                    type: ParsedTestCase::TYPE_PEST,
                    startLine: $call->getStartLine(),
                    endLine: $this->findEndLine($call),
                    existingCoversMethod: $this->findExistingCoversMethod($call),
                );
            }
        }

        return null;
    }

    /**
     * Find a test inside nested describe blocks.
     *
     * @param  array<Node>  $ast
     */
    private function findInDescribeBlocks(array $ast, string $fullPath): ?ParsedTestCase
    {
        $parts        = explode(' > ', $fullPath);
        $testName     = array_pop($parts);
        $describePath = $parts;

        // Navigate through describe blocks
        $currentScope = $ast;

        foreach ($describePath as $describeName) {
            $describeCall = $this->findDescribeBlock($currentScope, $describeName);

            if (!$describeCall instanceof \PhpParser\Node\Expr\FuncCall) {
                return null;
            }

            $currentScope = $this->getClosureBody($describeCall);

            if ($currentScope === null) {
                return null;
            }
        }

        // Find the test in the innermost scope (use direct search, not filtered)
        $testCalls = $this->findDirectTestCalls($currentScope);

        foreach ($testCalls as $call) {
            $name = $this->extractTestName($call);

            if ($name === $testName) {
                return new ParsedTestCase(
                    name: $testName,
                    type: ParsedTestCase::TYPE_PEST,
                    startLine: $call->getStartLine(),
                    endLine: $this->findEndLine($call),
                    existingCoversMethod: $this->findExistingCoversMethod($call),
                    describePath: $describePath,
                );
            }
        }

        return null;
    }

    /**
     * Find all tests inside describe blocks.
     *
     * @param  array<Node>  $ast
     * @param  list<string>  $currentPath
     *
     * @return list<ParsedTestCase>
     */
    private function findTestsInDescribeBlocks(array $ast, array $currentPath = []): array
    {
        $tests = [];

        // Only find describe blocks at the current level, not recursively
        $describeCalls = $this->findDirectDescribeCalls($ast);

        foreach ($describeCalls as $describe) {
            $describeName = $this->extractTestName($describe);

            if ($describeName === null) {
                continue;
            }

            $newPath = [...$currentPath, $describeName];
            $body    = $this->getClosureBody($describe);

            if ($body === null) {
                continue;
            }

            // Find tests in this describe block
            $testCalls = $this->findDirectTestCalls($body);

            foreach ($testCalls as $call) {
                $testName = $this->extractTestName($call);

                if ($testName === null) {
                    continue;
                }

                $tests[] = new ParsedTestCase(
                    name: $testName,
                    type: ParsedTestCase::TYPE_PEST,
                    startLine: $call->getStartLine(),
                    endLine: $this->findEndLine($call),
                    existingCoversMethod: $this->findExistingCoversMethod($call),
                    describePath: $newPath,
                );
            }

            // Recursively find tests in nested describe blocks
            $nestedTests = $this->findTestsInDescribeBlocks($body, $newPath);
            $tests       = [...$tests, ...$nestedTests];
        }

        return $tests;
    }

    /**
     * Find direct describe() calls at the current level (not recursive).
     *
     * @param  array<Node>  $nodes
     *
     * @return list<Node\Expr\FuncCall>
     */
    private function findDirectDescribeCalls(array $nodes): array
    {
        $calls = [];

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Expression
                && $node->expr instanceof Node\Expr\FuncCall
                && $this->getFunctionName($node->expr) === 'describe'
            ) {
                $calls[] = $node->expr;
            }
        }

        return $calls;
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
            if ($node instanceof Node\Stmt\Expression
                && $node->expr instanceof Node\Expr\FuncCall
            ) {
                $funcName = $this->getFunctionName($node->expr);

                if (in_array($funcName, ['test', 'it'], true)) {
                    $calls[] = $node->expr;
                }
            }

            // Also check method chains starting with test/it (wrapped in Expression)
            if ($node instanceof Node\Stmt\Expression
                && $node->expr instanceof Node\Expr\MethodCall
            ) {
                $rootCall = $this->getRootFuncCall($node->expr);

                if ($rootCall instanceof \PhpParser\Node\Expr\FuncCall) {
                    $funcName = $this->getFunctionName($rootCall);

                    if (in_array($funcName, ['test', 'it'], true)) {
                        $calls[] = $rootCall;
                    }
                }
            }

            // Handle arrow function bodies (raw expressions without Statement wrapper)
            if ($node instanceof Node\Expr\FuncCall) {
                $funcName = $this->getFunctionName($node);

                if (in_array($funcName, ['test', 'it'], true)) {
                    $calls[] = $node;
                }
            }

            // Handle arrow function bodies with method chains
            if ($node instanceof Node\Expr\MethodCall) {
                $rootCall = $this->getRootFuncCall($node);

                if ($rootCall instanceof \PhpParser\Node\Expr\FuncCall) {
                    $funcName = $this->getFunctionName($rootCall);

                    if (in_array($funcName, ['test', 'it'], true)) {
                        $calls[] = $rootCall;
                    }
                }
            }
        }

        return $calls;
    }

    /**
     * Find all test() and it() function calls.
     *
     * @param  array<Node>  $ast
     *
     * @return list<Node\Expr\FuncCall>
     */
    private function findTestCalls(array $ast): array
    {
        /** @var list<Node\Expr\FuncCall> $calls */
        $calls = $this->nodeFinder->find($ast, function (Node $node): bool {
            if (!$node instanceof Node\Expr\FuncCall) {
                return false;
            }

            $funcName = $this->getFunctionName($node);

            return in_array($funcName, ['test', 'it'], true);
        });

        // Filter out tests inside describe blocks for top-level search
        return array_values(array_filter($calls, function (Node\Expr\FuncCall $call): bool {
            /** @var Node|null $parent */
            $parent = $call->getAttribute('parent');

            while ($parent instanceof Node) {
                if ($parent instanceof Node\Expr\FuncCall
                    && $this->getFunctionName($parent) === 'describe'
                ) {
                    return false;
                }

                /** @var Node|null $parent */
                $parent = $parent->getAttribute('parent');
            }

            return true;
        }));
    }

    /**
     * Find a describe block by name.
     *
     * @param  array<Node>  $ast
     */
    private function findDescribeBlock(array $ast, string $name): ?Node\Expr\FuncCall
    {
        $describes = $this->nodeFinder->find($ast, function (Node $node) use ($name): bool {
            if (!$node instanceof Node\Expr\FuncCall) {
                return false;
            }

            if ($this->getFunctionName($node) !== 'describe') {
                return false;
            }

            return $this->extractTestName($node) === $name;
        });

        $first = $describes[0] ?? null;

        return $first instanceof Node\Expr\FuncCall ? $first : null;
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
     * Extract test name from a function call.
     */
    private function extractTestName(Node\Expr\FuncCall $call): ?string
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
     * Find existing links/linksAndCovers() calls in the chain.
     *
     * @return list<string>
     */
    private function findExistingCoversMethod(Node\Expr\FuncCall $call): array
    {
        $methods = [];
        $parent  = $call->getAttribute('parent');

        // Walk up the chain to find method calls
        while ($parent instanceof Node\Expr\MethodCall) {
            $methodName = $parent->name;

            if ($methodName instanceof Node\Identifier
                && in_array($methodName->toString(), ['linksAndCovers', 'links'], true)
            ) {
                foreach ($parent->args as $arg) {
                    if (!$arg instanceof Node\Arg) {
                        continue;
                    }

                    if ($arg->value instanceof Node\Scalar\String_) {
                        $methods[] = $arg->value->value;
                    }

                    // Handle concatenation: Class::class.'::method'
                    if ($arg->value instanceof Node\Expr\BinaryOp\Concat) {
                        $method = $this->extractConcatValue($arg->value);

                        if ($method !== null) {
                            $methods[] = $method;
                        }
                    }
                }
            }

            $parent = $parent->getAttribute('parent');
        }

        return $methods;
    }

    /**
     * Extract value from a concatenation like Class::class.'::method'.
     *
     * Uses the resolved FQN from NameResolver when available.
     */
    private function extractConcatValue(Node\Expr\BinaryOp\Concat $concat): ?string
    {
        $left  = $concat->left;
        $right = $concat->right;

        $leftValue  = null;
        $rightValue = null;

        if ($left instanceof Node\Expr\ClassConstFetch && $left->name instanceof Node\Identifier && $left->name->toString() === 'class' && $left->class instanceof Node\Name
        ) {
            // Use resolved name (FQN) if available, otherwise fall back to the original name
            /** @var Node\Name|null $resolvedName */
            $resolvedName = $left->class->getAttribute('resolvedName');
            $leftValue    = $resolvedName instanceof Node\Name
                ? $resolvedName->toString()
                : $left->class->toString();
        }

        if ($right instanceof Node\Scalar\String_) {
            $rightValue = $right->value;
        }

        if ($leftValue !== null && $rightValue !== null) {
            return $leftValue.$rightValue;
        }

        return null;
    }

    /**
     * Find the end line of a test call (including method chain).
     */
    private function findEndLine(Node\Expr\FuncCall $call): int
    {
        $endLine = $call->getEndLine();
        $parent  = $call->getAttribute('parent');

        while ($parent instanceof Node\Expr\MethodCall) {
            $endLine = max($endLine, $parent->getEndLine());
            $parent  = $parent->getAttribute('parent');
        }

        return $endLine;
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

        if ($current instanceof Node\Expr\FuncCall) {
            return $current;
        }

        return null;
    }
}
