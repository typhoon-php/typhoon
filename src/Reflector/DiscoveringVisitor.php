<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\AnonymousClassName;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\ParsingContext;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\TypeContext\TypeContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class DiscoveringVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly ParsingContext $parsingContext,
        private readonly TypeContext $typeContext,
        private readonly Resource $resource,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Stmt\ClassLike) {
            $name = $this->resolveClassName($node);
            $typeContext = clone $this->typeContext;
            $this->parsingContext->registerClassReflector(
                name: $name,
                reflector: fn(ClassReflector $classReflector): ClassReflection => PhpParserReflector::reflectClass(
                    classReflector: $classReflector,
                    typeContext: $typeContext,
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
        if ($node->name === null) {
            if (!$node instanceof Stmt\Class_) {
                throw new ReflectionException(sprintf('Unexpected %s with null name.', $node::class));
            }

            $name = AnonymousClassName::fromNode(
                file: $this->resource->file,
                node: $node,
                nameResolver: $this->typeContext,
            );

            return $name->toStringWithoutRtdKeyCounter();
        }

        return $this->typeContext->resolveNameAsClass($node->name->toString());
    }
}
