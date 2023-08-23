<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\Parser\Php7;
use Typhoon\Reflection\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\NameResolution\NameContext;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\Resource;
use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpParserReflector
{
    public function __construct(
        private readonly Parser $phpParser = new Php7(new Emulative(['usedAttributes' => ['comments', 'startLine', 'endLine']])),
        private readonly PhpDocParser $phpDocParser = new PhpDocParser(),
        private readonly PhpDocTypeReflector $phpDocTypeReflector = new PhpDocTypeReflector(),
    ) {}

    public function reflectResource(Resource $resource, ReflectionContext $reflectionContext): Reflections
    {
        $code = exceptionally(static fn (): string|false => file_get_contents($resource->file));
        $nodes = $this->phpParser->parse($code) ?? throw new \LogicException('Failed to parse code.');
        $reflections = new Reflections();

        if ($nodes === []) {
            return $reflections;
        }

        $nameContext = new NameContext();
        $nameContextVisitor = new NameContextVisitor(
            phpDocParser: $this->phpDocParser,
            nameContext: $nameContext,
        );
        $discoveringVisitor = new DiscoveringVisitor(
            phpDocParser: $this->phpDocParser,
            phpDocTypeReflector: $this->phpDocTypeReflector,
            reflectionContext: $reflectionContext,
            nameContext: $nameContext,
            reflections: $reflections,
            resource: $resource,
            changeDetector: FileChangeDetector::fromContents($resource->file, $code),
        );

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nameContextVisitor);
        $traverser->addVisitor($discoveringVisitor);
        $traverser->traverse($nodes);

        return $reflections;
    }
}
