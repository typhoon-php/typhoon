<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\ClassLocator\LoadedClassLocator;
use ExtendedTypeSystem\Reflection\PHPDocParser\PHPDocParser;
use ExtendedTypeSystem\Reflection\Scope\GlobalScope;
use ExtendedTypeSystem\Reflection\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;
use ExtendedTypeSystem\Reflection\TypeReflector\ClassLikeMetadata;
use ExtendedTypeSystem\Reflection\TypeReflector\ClassLikeReflectionVisitor;
use ExtendedTypeSystem\Reflection\TypeReflector\LRUMemoizer;
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
    private readonly LRUMemoizer $memoizer;

    private readonly PHPDocParser $phpDocParser;

    /**
     * @param int<0, max> $maxMemoizedReflections
     */
    public function __construct(
        private readonly ClassLocator $classLocator = new LoadedClassLocator(),
        private readonly Parser $phpParser = new Php7(new Emulative(['usedAttributes' => ['comments']])),
        PHPStanPhpDocParser $phpDocParser = new PHPStanPhpDocParser(new PHPStanTypeParser(new ConstExprParser()), new ConstExprParser()),
        PHPStanPhpDocLexer $phpDocLexer = new PHPStanPhpDocLexer(),
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
        int $maxMemoizedReflections = 1000,
    ) {
        $this->phpDocParser = new PHPDocParser($phpDocParser, $phpDocLexer, $tagPrioritizer);
        $this->memoizer = new LRUMemoizer($maxMemoizedReflections);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassLikeReflection<T>
     */
    public function reflectClassLike(string $class): ClassLikeReflection
    {
        return $this->reflectClassLikeMetadata($class)->reflection;
    }

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

    public function clearMemoizedReflections(): void
    {
        $this->memoizer->clear();
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassLikeMetadata<T>
     */
    private function reflectClassLikeMetadata(string $class): ClassLikeMetadata
    {
        return $this->memoizer->get($class, function () use ($class): ClassLikeMetadata {
            $source = $this->classLocator->locateClass($class);

            if ($source === null) {
                throw new TypeReflectionException(sprintf('Failed to locate class %s.', $class));
            }

            $visitor = new ClassLikeReflectionVisitor($class, $this->reflectClassLikeMetadata(...), $this->phpDocParser);
            $this->parseAndTraverse($source->code, $visitor);

            return $visitor->metadata
                ?? throw new TypeReflectionException(sprintf('No class %s in source %s.', $class, $source->description));
        });
    }

    private function parseAndTraverse(string $code, NodeVisitor $visitor): void
    {
        $statements = $this->phpParser->parse($code) ?? [];
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);
    }
}
