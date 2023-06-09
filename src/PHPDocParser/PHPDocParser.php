<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

use ExtendedTypeSystem\Reflection\TagPrioritizer;
use ExtendedTypeSystem\Reflection\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;
use ExtendedTypeSystem\Reflection\TypeReflectionException;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
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
        private readonly PHPStanPhpDocParser $parser = new PHPStanPhpDocParser(
            typeParser: new TypeParser(new ConstExprParser()),
            constantExprParser: new ConstExprParser(),
        ),
        private readonly Lexer $lexer = new PHPStanPhpDocLexer(),
        private readonly TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
    ) {
    }

    public function parseNode(Node $node): PHPDoc
    {
        $phpDoc = $node->getDocComment()?->getText() ?? '';

        if (trim($phpDoc) === '') {
            return new PHPDoc();
        }

        $tags = $this->parsePHPDoc($phpDoc);

        return (new PHPDocBuilder($this->tagPrioritizer))
            ->addTags($tags)
            ->build();
    }

    public function parseTypeFromString(string $type): TypeNode
    {
        $tags = $this->parsePHPDoc("/** @var {$type} */");
        $tag = reset($tags);

        if (!$tag->value instanceof VarTagValueNode) {
            throw new TypeReflectionException(sprintf('Invalid string type "%s".', $type));
        }

        return $tag->value->type;
    }

    /**
     * @return array<PhpDocTagNode>
     */
    private function parsePHPDoc(string $phpDoc): array
    {
        $tokens = $this->lexer->tokenize($phpDoc);

        return $this->parser->parse(new TokenIterator($tokens))->getTags();
    }
}
