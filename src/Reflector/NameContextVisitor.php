<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\NameResolution\NameContext;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NameContextVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly PhpDocParser $phpDocParser,
        private readonly NameContext $nameContext,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Stmt\Namespace_) {
            $this->nameContext->enterNamespace($node->name?->toString());

            return null;
        }

        if ($node instanceof Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->addUse(
                    type: $node->type,
                    name: $use->name,
                    alias: $use->getAlias(),
                );
            }

            return null;
        }

        if ($node instanceof Stmt\GroupUse) {
            foreach ($node->uses as $use) {
                $this->addUse(
                    type: $node->type | $use->type,
                    name: $use->name,
                    alias: $use->getAlias(),
                    prefix: $node->prefix,
                );
            }

            return null;
        }

        if ($node instanceof Stmt\ClassLike) {
            if ($node->name === null) {
                return null;
            }

            $this->nameContext->enterClass(
                name: $node->name->name,
                parent: $node instanceof Stmt\Class_ ? $node->extends?->toString() : null,
                templateNames: array_keys($this->phpDocParser->parseNodePhpDoc($node)->templates),
            );

            return null;
        }

        if ($node instanceof Stmt\ClassMethod) {
            $this->nameContext->enterMethod(
                name: $node->name->name,
                templateNames: array_keys($this->phpDocParser->parseNodePhpDoc($node)->templates),
            );

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Stmt\Namespace_) {
            $this->nameContext->leaveNamespace();

            return null;
        }

        if ($node instanceof Stmt\ClassLike) {
            if ($node->name === null) {
                return null;
            }

            $this->nameContext->leaveClass();

            return null;
        }

        if ($node instanceof Stmt\ClassMethod) {
            $this->nameContext->leaveMethod();

            return null;
        }

        return null;
    }

    private function addUse(int $type, Node\Name $name, Node\Identifier $alias, ?Node\Name $prefix = null): void
    {
        if ($type === Stmt\Use_::TYPE_NORMAL) {
            $this->nameContext->addUse($name->toString(), $alias->name, $prefix?->toString());

            return;
        }

        if ($type === Stmt\Use_::TYPE_CONSTANT) {
            $this->nameContext->addConstantUse($name->toString(), $alias->name, $prefix?->toString());

            return;
        }
    }
}
