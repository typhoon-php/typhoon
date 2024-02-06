<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class CorrectClassStartLineVisitor extends NodeVisitorAbstract
{
    private const ATTRIBUTE = 'class_correct_line';

    /**
     * @param string|array<\PhpToken> $code
     */
    public function __construct(
        private string|array $code,
    ) {}

    public static function getStartLine(Node $node): int
    {
        $line = $node->getAttribute(self::ATTRIBUTE, $node->getStartLine());
        \assert(\is_int($line));

        return $line;
    }

    public function enterNode(Node $node): ?int
    {
        if (!$node instanceof Node\Stmt\ClassLike) {
            return null;
        }

        $lastAttributeKey = array_key_last($node->attrGroups);

        if ($lastAttributeKey === null) {
            return null;
        }

        $node->setAttribute(self::ATTRIBUTE, $this->findFirstClassTokenLine($node->attrGroups[$lastAttributeKey]->getEndFilePos()));

        return null;
    }

    private function findFirstClassTokenLine(int $offset): int
    {
        if (\is_string($this->code)) {
            $this->code = \PhpToken::tokenize($this->code);
        }

        $token = current($this->code);

        if ($token === false || $token->pos < $offset) {
            $token = reset($this->code);
        }

        while ($token !== false) {
            if ($token->pos >= $offset && $token->is([T_FINAL, T_READONLY, T_ABSTRACT, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
                return $token->line;
            }

            $token = next($this->code);
        }

        throw new \LogicException();
    }
}
