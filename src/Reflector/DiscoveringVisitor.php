<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\AnonymousClassName;
use Typhoon\Reflection\ChangeDetector;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\NameResolution\NameContext;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\ReflectionException;
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
            $name = $this->resolveClassName($node);

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
                name: $name,
                reflectionLoader: static fn (): ClassReflection => $reflector->reflectClass($node, $name),
            );
        }

        return null;
    }

    /**
     * @return class-string
     */
    private function resolveClassName(Stmt\ClassLike $node): string
    {
        if ($node->name !== null) {
            return $this->nameContext->resolveNameAsClass($node->name->toString());
        }

        if (!$node instanceof Stmt\Class_) {
            throw new ReflectionException();
        }

        return AnonymousClassName::fromNode(file: $this->resource->file, node: $node, nameContext: $this->nameContext)
            ->toStringWithoutRtdKeyCounter();
    }
}
