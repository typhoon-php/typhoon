<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\ReflectionStorage\ChangeDetector;
use Typhoon\Reflection\ReflectionStorage\ReflectionStorage;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class ResourceVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly ReflectionStorage $reflectionStorage,
        private readonly ContextualPhpParserReflector $reflector,
        private readonly ChangeDetector $changeDetector,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $name = $this->reflector->resolveClassName($node->name);
            $reflector = clone $this->reflector;
            $this->reflectionStorage->setReflector(
                class: ClassReflection::class,
                name: $name,
                reflector: static fn(): ClassReflection => $reflector->reflectClass($node, $name),
                changeDetector: $this->changeDetector,
            );
        }

        return null;
    }
}
