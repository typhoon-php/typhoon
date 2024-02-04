<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\NameContext\AnonymousClassName;
use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class FindAnonymousClassVisitor extends NodeVisitorAbstract
{
    private ?Class_ $node = null;

    public function __construct(
        private readonly AnonymousClassName $name,
    ) {}

    public function node(): Class_
    {
        return $this->node ?? throw new ReflectionException();
    }

    public function enterNode(Node $node): ?int
    {
        if ($node->getLine() < $this->name->line) {
            return null;
        }

        if ($node->getLine() > $this->name->line) {
            return NodeTraverser::STOP_TRAVERSAL;
        }

        if (!$node instanceof Class_ || $node->name !== null) {
            return null;
        }

        if ($this->node !== null) {
            throw new ReflectionException(sprintf('More than 1 anonymous class at %s:%d.', $this->name->file, $this->name->line));
        }

        $this->node = $node;

        return null;
    }
}
