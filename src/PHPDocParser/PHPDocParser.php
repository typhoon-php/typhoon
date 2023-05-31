<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

use ExtendedTypeSystem\Reflection\TagPrioritizer;
use ExtendedTypeSystem\Reflection\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Lexer\Lexer as PHPStanPhpDocLexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 */
final class PHPDocParser
{
    public function __construct(
        private readonly PHPStanPhpDocParser $parser = new PHPStanPhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser()),
        private readonly Lexer $lexer = new PHPStanPhpDocLexer(),
        private readonly TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
    ) {
    }

    public function parse(Node $node): PHPDoc
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
