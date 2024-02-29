<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MetadataStorage;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class FileResourceVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly ContextualPhpParserReflector $reflector,
        private readonly MetadataStorage $metadata,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            /** @var class-string This is proved, because this is a file where class is declared */
            $name = $this->reflector->resolveNameAsClass($node->name);
            $reflector = clone $this->reflector;
            $this->metadata->saveDeferred(
                class: ClassMetadata::class,
                name: $name,
                metadata: static fn(): ClassMetadata => $reflector->reflectClass($node, $name),
            );

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
