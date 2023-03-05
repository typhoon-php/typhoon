<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\ClassLocator\LoadedClassLocator;
use ExtendedTypeSystem\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;
use ExtendedTypeSystem\TypeReflector\ClassReflectionFactory;
use ExtendedTypeSystem\TypeReflector\FindClassVisitor;
use ExtendedTypeSystem\TypeReflector\PHPDocParser;
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
    private readonly ClassReflectionFactory $classReflectionFactory;

    public function __construct(
        private readonly ClassLocator $classLocator = new LoadedClassLocator(),
        private readonly Parser $phpParser = new Php7(new Emulative(['usedAttributes' => ['comments']])),
        PHPStanPhpDocParser $phpDocParser = new PHPStanPhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser()),
        PHPStanPhpDocLexer $phpDocLexer = new PHPStanPhpDocLexer(),
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
    ) {
        $this->classReflectionFactory = new ClassReflectionFactory(
            phpDocParser: new PHPDocParser($phpDocParser, $phpDocLexer, $tagPrioritizer),
        );
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
            throw new \LogicException(sprintf('Failed to locate class %s.', $class));
        }

        $statements = $this->phpParser->parse($source->code) ?? [];
        $traverser = new NodeTraverser();
        $nameResolver = new NameResolver();
        $findClassVisitor = new FindClassVisitor($class);
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($findClassVisitor);
        $traverser->traverse($statements);

        if ($findClassVisitor->node === null) {
            throw new \LogicException(sprintf('Class %s was not found in %s.', $class, $source->description));
        }

        return $this->classReflectionFactory->build($this, $class, $nameResolver->getNameContext(), $findClassVisitor->node);
    }
}
