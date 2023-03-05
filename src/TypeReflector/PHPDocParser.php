<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\TagPrioritizer;
use ExtendedTypeSystem\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class PHPDocParser
{
    public function __construct(
        private readonly PHPStanPhpDocParser $parser = new PHPStanPhpDocParser(
            new TypeParser(new ConstExprParser()),
            new ConstExprParser(),
        ),
        private readonly Lexer $lexer = new Lexer(),
        private readonly TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
    ) {
    }

    /**
     * @return list<PhpDocTagNode>
     */
    public function parseNodePHPDoc(Node $node): array
    {
        $phpDoc = $node->getDocComment()?->getText() ?? '';

        if (trim($phpDoc) === '') {
            return [];
        }

        $tokens = $this->lexer->tokenize($phpDoc);
        $tags = $this->parser->parse(new TokenIterator($tokens))->getTags();
        usort(
            $tags,
            fn (PhpDocTagNode $a, PhpDocTagNode $b): int => $this->tagPrioritizer->priorityFor($b->name) <=> $this->tagPrioritizer->priorityFor($a->name),
        );

        return $tags;
    }
}
