<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MetadataLazyCollection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class ResourceVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly ContextualPhpParserReflector $reflector,
        private readonly MetadataLazyCollection $metadata,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $name = $this->reflector->resolveClassName($node->name);
            $reflector = clone $this->reflector;
            $this->metadata->setFactory(
                class: ClassMetadata::class,
                name: $name,
                factory: static fn(): ClassMetadata => $reflector->reflectClass($node, $name),
            );

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
