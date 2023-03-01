<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\NameResolution;

use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class NameResolverFactory
{
    public function __construct(
        private readonly ContextFactory $contextFactory = new ContextFactory(),
        private readonly FqsenResolver $fqsenResolver = new FqsenResolver(),
    ) {
    }

    public function create(string|false $file, string $namespace): NameResolver
    {
        if ($file === false) {
            return new NameResolver($this->fqsenResolver, new Context(''));
        }

        $contents = @file_get_contents($file);

        if ($contents === false) {
            return new NameResolver($this->fqsenResolver, new Context(''));
        }

        return new NameResolver(
            fqsenResolver: $this->fqsenResolver,
            context: $this->contextFactory->createForNamespace(
                namespace: $namespace,
                fileContents: $contents,
            ),
        );
    }
}
