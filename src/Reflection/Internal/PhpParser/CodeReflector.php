<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Internal\IdMap;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Annotated\AnnotatedDeclarationsDiscoverer;
use Typhoon\Reflection\Internal\Context\ContextVisitor;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CodeReflector
{
    public function __construct(
        private readonly Parser $phpParser,
        private readonly AnnotatedDeclarationsDiscoverer $annotatedDeclarationsDiscoverer,
        private readonly NodeReflector $nodeReflector,
    ) {}

    /**
     * @param ?non-empty-string $file
     * @return IdMap<ConstantId|NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(): TypedMap>
     */
    public function reflectCode(string $code, ?string $file = null): IdMap
    {
        $nodes = $this->phpParser->parse($code) ?? throw new \LogicException();

        /** @psalm-suppress MixedArgument, ArgumentTypeCoercion, UnusedPsalmSuppress */
        $linesFixer = method_exists($this->phpParser, 'getTokens')
            ? new FixNodeLocationVisitor($this->phpParser->getTokens())
            : FixNodeLocationVisitor::fromCode($code);
        $nameResolver = new NameResolver();
        $contextVisitor = new ContextVisitor(
            code: $code,
            file: $file,
            nameContext: $nameResolver->getNameContext(),
            annotatedDeclarationsDiscoverer: $this->annotatedDeclarationsDiscoverer,
        );
        $collector = new CollectIdReflectorsVisitor($this->nodeReflector, $contextVisitor);

        $traverser = new NodeTraverser();

        if (!PhpParserChecker::isVisitorLeaveReversed()) {
            $traverser->addVisitor($collector);
        }

        $traverser->addVisitor($linesFixer);
        $traverser->addVisitor(new GeneratorVisitor());
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($contextVisitor);

        if (PhpParserChecker::isVisitorLeaveReversed()) {
            $traverser->addVisitor($collector);
        }

        $traverser->traverse($nodes);

        return $collector->idReflectors;
    }
}
