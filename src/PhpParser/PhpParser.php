<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParser;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\Parser\Php7;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpParser
{
    public function __construct(
        private readonly Parser $phpParser = new Php7(
            new Emulative(['usedAttributes' => ['comments', 'startLine', 'endLine']]),
        ),
    ) {}

    /**
     * @param iterable<NodeVisitor> $visitors
     */
    public function parseAndTraverse(string $code, iterable $visitors): void
    {
        $nodes = $this->phpParser->parse($code) ?? throw new \LogicException('Failed to parse code.');
        $traverser = new NodeTraverser();
        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }
        $traverser->traverse($nodes);
    }
}
