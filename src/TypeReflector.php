<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\ClassLocator\LoadedClassLocator;
use ExtendedTypeSystem\Reflection\PHPDocParser\PHPDocParser;
use ExtendedTypeSystem\Reflection\Scope\GlobalScope;
use ExtendedTypeSystem\Reflection\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;
use ExtendedTypeSystem\Reflection\TypeReflector\ClassLikeReflectionVisitor;
use ExtendedTypeSystem\Reflection\TypeReflector\TypeParser;
use ExtendedTypeSystem\Type;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\Parser\Php7;
use PHPStan\PhpDocParser\Lexer\Lexer as PHPStanPhpDocLexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser as PHPStanTypeParser;

/**
 * @api
 */
final class TypeReflector
{
    private readonly PHPDocParser $phpDocParser;

    public function __construct(
        private readonly ClassLocator $classLocator = new LoadedClassLocator(),
        private readonly Parser $phpParser = new Php7(new Emulative(['usedAttributes' => ['comments']])),
        PHPStanPhpDocParser $phpDocParser = new PHPStanPhpDocParser(new PHPStanTypeParser(new ConstExprParser()), new ConstExprParser()),
        PHPStanPhpDocLexer $phpDocLexer = new PHPStanPhpDocLexer(),
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
    ) {
        $this->phpDocParser = new PHPDocParser($phpDocParser, $phpDocLexer, $tagPrioritizer);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassLikeReflection<T>
     * @throws TypeReflectionException
     */
    public function reflectClassLike(string $class): ClassLikeReflection
    {
        $source = $this->classLocator->locateClass($class);

        if ($source === null) {
            throw new \RuntimeException(sprintf('Failed to locate class %s.', $class));
        }

        $visitor = new ClassLikeReflectionVisitor($class, $this, $this->phpDocParser);
        $this->parseAndTraverse($source->code, $visitor);

        return $visitor->reflection
            ?? throw new TypeReflectionException(sprintf('No class %s in source %s.', $class, $source->description));
    }

    /**
     * @throws TypeReflectionException
     */
    public function reflectTypeFromString(string $type, Scope $scope = new GlobalScope()): ?Type
    {
        $typeNode = $this->phpDocParser->parseTypeFromString($type);

        return TypeParser::parsePHPDocType($scope, $typeNode);
    }

    /**
     * @return ($type is null ? null : Type)
     */
    public function reflectReflectionType(?\ReflectionType $type, Scope $scope = new GlobalScope()): ?Type
    {
        return TypeParser::parseReflectionType($scope, $type);
    }

    private function parseAndTraverse(string $code, NodeVisitor $visitor): void
    {
        $statements = $this->phpParser->parse($code) ?? [];
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);
    }
}
