<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\TypeReflector\ClassVisitor;
use ExtendedTypeSystem\TypeReflector\PHPDocParser;
use ExtendedTypeSystem\TypeReflector\TypeResolver;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\Parser\Php7;
use PHPStan\PhpDocParser\Lexer\Lexer as PHPStanPhpDocLexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;

/**
 * @psalm-api
 */
final class TypeReflector
{
    private readonly PHPDocParser $phpDocParser;
    private readonly TypeResolver $typeResolver;

    public function __construct(
        private readonly ClassLocator $classLocator = new LoadedClassLocator(),
        private readonly Parser $phpParser = new Php7(new Emulative(['usedAttributes' => ['comments']])),
        PHPStanPhpDocParser $phpDocParser = new PHPStanPhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser()),
        PHPStanPhpDocLexer $phpDocLexer = new PHPStanPhpDocLexer(),
        PHPDocTagPrioritizer $phpDocPrioritizer = new PHPStanOverPsalmOverOtherPHPDocTagPrioritizer(),
    ) {
        $this->phpDocParser = new PHPDocParser($phpDocParser, $phpDocLexer, $phpDocPrioritizer);
        $this->typeResolver = new TypeResolver();
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassLikeTypeReflection<T>
     */
    public function reflectClass(string $class): ClassLikeTypeReflection
    {
        $source = $this->classLocator->locateClass($class);

        if ($source === null) {
            throw new \LogicException('todo');
        }

        $statements = $this->phpParser->parse($source->code);

        if (!$statements) {
            throw new \LogicException('todo');
        }

        $nameResolver = new NameResolver();
        $classVisitor = new ClassVisitor(
            typeReflector: $this,
            nameContext: $nameResolver->getNameContext(),
            typeResolver: $this->typeResolver,
            phpDocParser: $this->phpDocParser,
            class: $class,
        );

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($classVisitor);
        $traverser->traverse($statements);

        return $classVisitor->reflection ?? throw new \LogicException('todo');
    }
}
