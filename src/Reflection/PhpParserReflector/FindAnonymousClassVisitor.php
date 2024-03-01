<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\NameContext\AnonymousClassName;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class FindAnonymousClassVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     */
    public ?Class_ $node = null;

    public function __construct(
        private readonly AnonymousClassName $name,
    ) {}

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
            throw new MultipleAnonymousClassesOnLine($this->name->file, $this->name->line);
        }

        $this->node = $node;

        return null;
    }
}
