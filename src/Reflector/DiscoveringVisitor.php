<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\AnonymousClassName;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\NameContext\NameContext;
use Typhoon\Reflection\ParsingContext;
use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-import-type TemplateReflector from NameAsTypeResolution
 */
final class DiscoveringVisitor extends NodeVisitorAbstract
{
    /**
     * @param NameContext<TemplateReflector> $nameContext
     */
    public function __construct(
        private readonly ParsingContext $parsingContext,
        private readonly ClassExistenceChecker $classExistenceChecker,
        private readonly NameContext $nameContext,
        private readonly Resource $resource,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Stmt\ClassLike) {
            $name = $this->resolveClassName($node);
            $nameContext = clone $this->nameContext;
            $this->parsingContext->registerClassReflector(
                name: $name,
                reflector: fn(): ClassReflection => PhpParserReflector::reflectClass(
                    classExistenceChecker: $this->classExistenceChecker,
                    nameContext: $nameContext,
                    resource: $this->resource,
                    node: $node,
                    name: $name,
                ),
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

        $name = AnonymousClassName::fromNode(
            file: $this->resource->file,
            node: $node,
            nameResolver: $this->nameContext,
        );

        return $name->toStringWithoutRtdKeyCounter();
    }
}
