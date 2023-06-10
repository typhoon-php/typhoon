<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Reflector;

use ExtendedTypeSystem\Reflection\ClassReflection;
use ExtendedTypeSystem\Reflection\NameContext;
use ExtendedTypeSystem\Reflection\Reflector;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class DiscoveringVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly PhpParserReflector $phpParserReflector,
        private readonly Reflector $reflector,
        private readonly NameContext $nameContext,
        private readonly Reflections $reflections,
    ) {
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Stmt\ClassLike) {
            if ($node->name === null) {
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            $nameContext = clone $this->nameContext;

            $this->reflections->addLazy(
                class: ClassReflection::class,
                name: $nameContext->resolveNameAsClass($node->name->toString()),
                reflection: fn (): ClassReflection => $this->phpParserReflector->reflectClass($node, $nameContext, $this->reflector),
            );

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
