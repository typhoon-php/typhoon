<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser as PhpParser;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\FileResource;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MetadataStorage;
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

    public function reflectFile(FileResource $file, ClassExistenceChecker $classExistenceChecker, MetadataStorage $metadata): void
    {
        $nameContext = new NameContext();
        $this->parseAndTraverse($file->contents(), [
            new NameContextVisitor($nameContext),
            new FileResourceVisitor(
                reflector: new ContextualPhpParserReflector(
                    phpDocParser: $this->phpDocParser,
                    typeContext: new TypeContext(
                        nameResolver: $nameContext,
                        classExistenceChecker: $classExistenceChecker,
                    ),
                    file: $file,
                ),
                metadata: $metadata,
            ),
        ]);
    }

    public function reflectAnonymousClass(AnonymousClassName $name): ClassMetadata
    {
        $file = new FileResource($name->file);
        $nameContext = new NameContext();
        $visitor = new FindAnonymousClassVisitor($name);
        $this->parseAndTraverse($file->contents(), [new NameContextVisitor($nameContext), $visitor]);

        if ($visitor->node === null) {
            throw new ClassDoesNotExist($name->toString());
        }

        $reflector = new ContextualPhpParserReflector(
            phpDocParser: $this->phpDocParser,
            typeContext: new TypeContext($nameContext),
            file: $file,
        );

        return $reflector->reflectClass($visitor->node, $name->toString());
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

        try {
            $nodes = $this->phpParser->parse($code) ?? throw new ParserError('Failed to parse code');
        } catch (Error $error) {
            throw new ParserError($error->getMessage(), previous: $error);
        }

        $traverser->traverse($nodes);
    }
}
