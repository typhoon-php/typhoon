<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Parser;

use ExtendedTypeSystem\PHPStanOverPsalmOverOtherTagsPrioritizer;
use ExtendedTypeSystem\TagPrioritizer;
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
        private readonly TagPrioritizer $prioritizer = new PHPStanOverPsalmOverOtherTagsPrioritizer(),
    ) {
    }

    public function parsePhpDoc(string|false $phpDoc): PHPDocTags
    {
        if (!$phpDoc) {
            return new PHPDocTags([]);
        }

        $tokens = $this->lexer->tokenize($phpDoc);
        $tags = $this->parser->parse(new TokenIterator($tokens))->getTags();
        usort(
            $tags,
            fn (PhpDocTagNode $a, PhpDocTagNode $b): int => $this->prioritizer->priorityFor($b->name) <=> $this->prioritizer->priorityFor($a->name),
        );

        return new PHPDocTags($tags);
    }
}
