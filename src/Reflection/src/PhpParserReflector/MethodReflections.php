<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class MethodReflections
{
    private function __construct() {}

    public static function isGenerator(ClassMethod $node): bool
    {
        $traverser = new NodeTraverser();
        $visitor = new class () extends NodeVisitorAbstract {
            public bool $hasYield = false;

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof Yield_) {
                    $this->hasYield = true;

                    return NodeTraverser::STOP_TRAVERSAL;
                }

                return null;
            }
        };
        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        return $visitor->hasYield;
    }

    /**
     * @return int-mask-of<\ReflectionMethod::IS_*>
     */
    public static function modifiers(ClassMethod $node, bool $interface): int
    {
        return ($node->isStatic() ? \ReflectionMethod::IS_STATIC : 0)
            | ($node->isPublic() ? \ReflectionMethod::IS_PUBLIC : 0)
            | ($node->isProtected() ? \ReflectionMethod::IS_PROTECTED : 0)
            | ($node->isPrivate() ? \ReflectionMethod::IS_PRIVATE : 0)
            | (($interface || $node->isAbstract()) ? \ReflectionMethod::IS_ABSTRACT : 0)
            | ($node->isFinal() ? \ReflectionMethod::IS_FINAL : 0);
    }
}
