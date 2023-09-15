<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\PhpDocParser\PhpDoc;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpDocParsingVisitor extends NodeVisitorAbstract
{
    private const ATTRIBUTE = 'php_doc';

    public function __construct(
        private readonly PhpDocParser $phpDocParser,
    ) {}

    public static function fromNode(Node $node): PhpDoc
    {
        /** @var PhpDoc */
        return $node->getAttribute(self::ATTRIBUTE) ?? PhpDoc::empty();
    }

    public function enterNode(Node $node): ?int
    {
        if (!(
            $node instanceof Node\Stmt\ClassLike
            || $node instanceof Node\Stmt\Property
            || $node instanceof Node\Stmt\ClassMethod
        )) {
            return null;
        }

        $text = $node->getDocComment()?->getText();

        if (!$text) {
            return null;
        }

        $text = $this->phpDocParser->parsePhpDoc($text);
        $node->setAttribute(self::ATTRIBUTE, $text);

        return null;
    }
}
