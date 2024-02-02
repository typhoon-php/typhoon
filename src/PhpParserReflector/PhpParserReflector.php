<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser as PhpParser;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\NameContext\NameContext;
use Typhoon\Reflection\NameContext\NameContextVisitor;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\ReflectionStorage\ChangeDetector;
use Typhoon\Reflection\ReflectionStorage\ReflectionStorage;
use Typhoon\Reflection\Resource;
use Typhoon\Reflection\TypeContext\TypeContext;
use function Typhoon\Reflection\Exceptionally\exceptionally;

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

    public function reflectResource(Resource $resource, ReflectionStorage $reflectionStorage, ClassReflector $classReflector): void
    {
        $contents = exceptionally(static fn(): string|false => file_get_contents($resource->file));
        $nameContext = new NameContext();
        $typeContext = new TypeContext($nameContext, $classReflector);
        $reflector = new ContextualPhpParserReflector(
            phpDocParser: $this->phpDocParser,
            classReflector: $classReflector,
            typeContext: $typeContext,
            resource: $resource,
        );
        $this->parseAndTraverse($contents, [
            new NameContextVisitor($nameContext),
            new ReflectResourceVisitor(
                reflectionStorage: $reflectionStorage,
                reflector: $reflector,
                changeDetector: ChangeDetector::fromFile($resource->file, $contents),
            ),
        ]);
    }

    /**
     * @param list<NodeVisitor> $visitors
     */
    private function parseAndTraverse(string $code, array $visitors): void
    {
        $traverser = new NodeTraverser();
        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }
        $traverser->traverse($this->phpParser->parse($code) ?? throw new ReflectionException('Failed to parse code.'));
    }
}
