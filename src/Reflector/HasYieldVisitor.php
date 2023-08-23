<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class HasYieldVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     */
    public bool $hasYield = false;

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Expr\Yield_) {
            $this->hasYield = true;

            return NodeTraverser::STOP_TRAVERSAL;
        }

        return null;
    }
}
