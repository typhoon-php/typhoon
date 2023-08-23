<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class NameCollector extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var list<string>
     */
    public array $classes = [];

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassLike && $node->namespacedName !== null) {
            $this->classes[] = $node->namespacedName->toString();

            return null;
        }

        return null;
    }
}
