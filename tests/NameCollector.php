<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser\Php7;

final class NameCollector extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var list<string>
     */
    public array $classes = [];

    private function __construct() {}

    public static function collect(string $file): self
    {
        $phpParser = new Php7(new Emulative());
        $nodes = $phpParser->parse(file_get_contents($file)) ?? [];
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $nameCollector = new self();
        $traverser->addVisitor($nameCollector);
        $traverser->traverse($nodes);

        return $nameCollector;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassLike && $node->namespacedName !== null) {
            $this->classes[] = $node->namespacedName->toString();

            return null;
        }

        return null;
    }
}
