<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 * @template T of object
 */
final class FindClassVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     */
    public ?ClassLikeNode $node = null;

    /**
     * @param class-string<T> $name
     */
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof ClassLikeNode) {
            return null;
        }

        if ($node->namespacedName?->toString() === $this->name) {
            $this->node = $node;

            return NodeTraverser::STOP_TRAVERSAL;
        }

        return NodeTraverser::DONT_TRAVERSE_CHILDREN;
    }
}
