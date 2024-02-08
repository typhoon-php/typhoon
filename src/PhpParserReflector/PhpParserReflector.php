<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser as PhpParser;
use Typhoon\Reflection\Exception\DefaultReflectionException;
use Typhoon\Reflection\FileResource;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MetadataLazyCollection;
use Typhoon\Reflection\NameContext\AnonymousClassName;
use Typhoon\Reflection\NameContext\NameContext;
use Typhoon\Reflection\NameContext\NameContextVisitor;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\TypeContext\ClassExistenceChecker;
use Typhoon\Reflection\TypeContext\TypeContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpParserReflector
{
    public function __construct(
        private readonly PhpParser $phpParser,
        private readonly PhpDocParser $phpDocParser,
    ) {}

    public function reflectFile(FileResource $file, MetadataLazyCollection $storage, ClassExistenceChecker $classExistenceChecker): void
    {
        $nameContext = new NameContext();
        $typeContext = new TypeContext($nameContext, $classExistenceChecker);
        $reflector = new ContextualPhpParserReflector(
            phpDocParser: $this->phpDocParser,
            typeContext: $typeContext,
            file: $file,
        );
        $this->parseAndTraverse($file->contents(), [
            new NameContextVisitor($nameContext),
            new ResourceVisitor($reflector, $storage),
        ]);
    }

    public function reflectAnonymousClass(AnonymousClassName $name): ClassMetadata
    {
        $file = new FileResource($name->file);
        $nameContext = new NameContext();
        $visitor = new FindAnonymousClassVisitor($name);
        $this->parseAndTraverse($file->contents(), [new NameContextVisitor($nameContext), $visitor]);
        $node = $visitor->node();
        $reflector = new ContextualPhpParserReflector(
            phpDocParser: $this->phpDocParser,
            typeContext: new TypeContext($nameContext),
            file: $file,
        );

        return $reflector->reflectClass($node, $name->name);
    }

    /**
     * @param list<NodeVisitor> $visitors
     */
    private function parseAndTraverse(string $code, array $visitors): void
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new FixNodeStartLineVisitor($code));
        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }
        $traverser->traverse($this->phpParser->parse($code) ?? throw new DefaultReflectionException('Failed to parse code.'));
    }
}
