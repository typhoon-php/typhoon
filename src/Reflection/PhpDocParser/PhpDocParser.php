<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpDocParser
{
    public function __construct(
        private readonly TagPrioritizer $tagPrioritizer = new PrefixBasedTagPrioritizer(),
        private readonly PHPStanPhpDocParser $parser = new PHPStanPhpDocParser(
            typeParser: new TypeParser(new ConstExprParser()),
            constantExprParser: new ConstExprParser(),
            requireWhitespaceBeforeDescription: true,
        ),
        private readonly Lexer $lexer = new Lexer(),
    ) {}

    public function parsePhpDoc(string $phpDoc): PhpDoc
    {
        $tokens = $this->lexer->tokenize($phpDoc);
        $phpDoc = $this->parser->parse(new TokenIterator($tokens));

        return new PhpDoc($this->tagPrioritizer, $phpDoc->getTags());
    }
}
