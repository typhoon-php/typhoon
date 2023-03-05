<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\TagPrioritizer;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class PHPDocParser
{
    public function __construct(
        private readonly PHPStanPhpDocParser $parser,
        private readonly Lexer $lexer,
        private readonly TagPrioritizer $tagPrioritizer,
    ) {
    }

    public function parseNode(Node $node): PHPDoc
    {
        $phpDoc = $node->getDocComment()?->getText() ?? '';

        if (trim($phpDoc) === '') {
            return new PHPDoc();
        }

        $tokens = $this->lexer->tokenize($phpDoc);
        $tags = $this->parser->parse(new TokenIterator($tokens))->getTags();
        usort(
            $tags,
            fn (PhpDocTagNode $a, PhpDocTagNode $b): int => $this->tagPrioritizer->priorityFor($b->name) <=> $this->tagPrioritizer->priorityFor($a->name),
        );

        return new PHPDoc($tags);
    }
}
