<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\ChangeDetector;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\NameResolution\NameContext;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\Resource;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class DiscoveringVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly PhpDocParser $phpDocParser,
        private readonly PhpDocTypeReflector $phpDocTypeReflector,
        private readonly ReflectionContext $reflectionContext,
        private readonly NameContext $nameContext,
        private readonly Reflections $reflections,
        private readonly Resource $resource,
        private readonly ChangeDetector $changeDetector,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Stmt\ClassLike) {
            if ($node->name === null) {
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            $reflector = new PhpParserStatementReflector(
                phpDocParser: $this->phpDocParser,
                phpDocTypeReflector: $this->phpDocTypeReflector,
                nameContext: clone $this->nameContext,
                reflectionContext: $this->reflectionContext,
                resource: $this->resource,
                changeDetector: $this->changeDetector,
            );

            $this->reflections->setLazy(
                class: ClassReflection::class,
                name: $this->nameContext->resolveNameAsClass($node->name->toString()),
                reflectionLoader: static fn (): ClassReflection => $reflector->reflectClass($node),
            );

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
