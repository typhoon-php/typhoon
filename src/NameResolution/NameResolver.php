<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\NameResolution;

use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Types\Context;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class NameResolver
{
    public function __construct(
        private readonly FqsenResolver $fqsenResolver,
        private readonly Context $context,
    ) {
    }

    public function resolveName(string $name): string
    {
        return (string) $this->fqsenResolver->resolve($name, $this->context);
    }
}
