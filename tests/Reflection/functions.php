<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Reflection\Internal\PhpDoc\AlwaysTrimmingConstExprParser;
use Typhoon\Reflection\Internal\PhpDoc\PhpDocTypeReflector;
use Typhoon\Type\Type;

function typeFromString(string $type, ?Context $context = null): Type
{
    $lexer = new Lexer();
    $typeParser = new TypeParser(new AlwaysTrimmingConstExprParser(unescapeStrings: true));
    $phpDocTypeReflector = new PhpDocTypeReflector($context ?? Context::start($type));

    $tokens = new TokenIterator($lexer->tokenize($type));
    $typeNode = $typeParser->parse($tokens);

    return $phpDocTypeReflector->reflectType($typeNode);
}
