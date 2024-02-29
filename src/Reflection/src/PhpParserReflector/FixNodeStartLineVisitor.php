<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class FixNodeStartLineVisitor extends NodeVisitorAbstract
{
    private const START_LINE_ATTRIBUTE = 'startLine';

    /**
     * @param string|array<\PhpToken> $code
     */
    public function __construct(
        private string|array $code,
    ) {}

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassLike && $node->attrGroups !== []) {
            $node->setAttribute(self::START_LINE_ATTRIBUTE, $this->findFirstTokenLine(
                end($node->attrGroups)->getEndFilePos(),
                [T_FINAL, T_READONLY, T_ABSTRACT, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM],
            ));

            return null;
        }

        if ($node instanceof Node\Stmt\ClassMethod && $node->attrGroups !== []) {
            $node->setAttribute(self::START_LINE_ATTRIBUTE, $this->findFirstTokenLine(
                end($node->attrGroups)->getEndFilePos(),
                [T_FINAL, T_ABSTRACT, T_STATIC, T_FUNCTION],
            ));

            return null;
        }

        return null;
    }

    /**
     * @param list<int> $tokenKinds
     */
    private function findFirstTokenLine(int $offset, array $tokenKinds): int
    {
        if (\is_string($this->code)) {
            $this->code = \PhpToken::tokenize($this->code);
        }

        $token = current($this->code);

        if ($token === false || $token->pos < $offset) {
            $token = reset($this->code);
        }

        while ($token !== false) {
            if ($token->pos >= $offset && $token->is($tokenKinds)) {
                return $token->line;
            }

            $token = next($this->code);
        }

        throw new \LogicException();
    }
}
