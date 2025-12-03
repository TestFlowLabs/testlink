<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Modifier;

use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * Removes orphaned links()/linksAndCovers() calls from test files.
 */
final class OrphanPruner
{
    private readonly Parser $parser;
    private readonly NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->parser     = (new ParserFactory())->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder();
    }

    /**
     * Find orphaned links()/linksAndCovers() calls in a file.
     *
     * @return list<OrphanedCall>
     */
    public function findOrphans(string $filePath, TestLinkRegistry $validRegistry): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $code = file_get_contents($filePath);

        if ($code === false) {
            return [];
        }

        $ast = $this->parser->parse($code);

        if ($ast === null) {
            return [];
        }

        // Connect parent nodes
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectingVisitor());
        $ast = $traverser->traverse($ast);

        $orphans = [];

        // Find all links/linksAndCovers calls
        /** @var list<Node\Expr\MethodCall> $methodCalls */
        $methodCalls = $this->nodeFinder->find($ast, function (Node $node): bool {
            if (!$node instanceof Node\Expr\MethodCall) {
                return false;
            }

            $name = $node->name;

            if (!$name instanceof Node\Identifier) {
                return false;
            }

            return in_array($name->toString(), ['linksAndCovers', 'links'], true);
        });

        foreach ($methodCalls as $call) {
            $coveredMethod = $this->extractCoveredMethod($call);

            if ($coveredMethod === null) {
                continue;
            }

            // Check if this method exists in the valid registry
            if (!$validRegistry->hasMethod($coveredMethod)) {
                $orphans[] = new OrphanedCall(
                    method: $coveredMethod,
                    line: $call->getStartLine(),
                    endLine: $call->getEndLine(),
                );
            }
        }

        return $orphans;
    }

    /**
     * Remove orphaned calls from code.
     *
     * @param  list<OrphanedCall>  $orphans
     */
    public function prune(string $code, array $orphans): string
    {
        if ($orphans === []) {
            return $code;
        }

        $lines = explode("\n", $code);

        // Sort orphans by line number descending to avoid offset issues
        usort($orphans, fn (OrphanedCall $a, OrphanedCall $b): int => $b->line <=> $a->line);

        foreach ($orphans as $orphan) {
            // Search from start line to end line for the actual call
            // (PHP-Parser reports startLine as chain start, not method call location)
            for ($lineNumber = $orphan->line; $lineNumber <= $orphan->endLine; $lineNumber++) {
                $lineIndex = $lineNumber - 1;

                if (!isset($lines[$lineIndex])) {
                    continue;
                }

                $line = $lines[$lineIndex];

                // Remove the method call from the line
                $pattern = '/->(?:linksAndCovers|links)\s*\(\s*[^)]*\)/';

                $newLine = preg_replace($pattern, '', $line, 1) ?? $line;

                if ($newLine !== $line) {
                    $lines[$lineIndex] = $newLine;

                    // Clean up empty lines that might result
                    if (trim($lines[$lineIndex]) === '') {
                        unset($lines[$lineIndex]);
                    }

                    break; // Found and removed, move to next orphan
                }
            }
        }

        return implode("\n", array_values($lines));
    }

    /**
     * Extract the linked method from a links/linksAndCovers call.
     */
    private function extractCoveredMethod(Node\Expr\MethodCall $call): ?string
    {
        $args = $call->args;

        if ($args === []) {
            return null;
        }

        $firstArg = $args[0];

        if (!$firstArg instanceof Node\Arg) {
            return null;
        }

        $value = $firstArg->value;

        // Simple string argument
        if ($value instanceof Node\Scalar\String_) {
            return $value->value;
        }

        // Concatenation: Class::class.'::method'
        if ($value instanceof Node\Expr\BinaryOp\Concat) {
            return $this->extractConcatValue($value);
        }

        return null;
    }

    /**
     * Extract value from concatenation like Class::class.'::method'.
     */
    private function extractConcatValue(Node\Expr\BinaryOp\Concat $concat): ?string
    {
        $left  = $concat->left;
        $right = $concat->right;

        $leftValue  = null;
        $rightValue = null;

        if ($left instanceof Node\Expr\ClassConstFetch && $left->name instanceof Node\Identifier && $left->name->toString() === 'class' && $left->class instanceof Node\Name
        ) {
            $leftValue = $left->class->toString();
        }

        if ($right instanceof Node\Scalar\String_) {
            $rightValue = $right->value;
        }

        if ($leftValue !== null && $rightValue !== null) {
            return $leftValue.$rightValue;
        }

        return null;
    }
}

/**
 * Value object for an orphaned links/linksAndCovers call.
 */
final readonly class OrphanedCall
{
    public function __construct(
        public string $method,
        public int $line,
        public int $endLine,
    ) {}
}
