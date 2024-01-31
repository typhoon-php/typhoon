<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TTemplateMetadata
 */
abstract class NameResolver
{
    /**
     * @return class-string
     */
    final public function resolveNameAsClass(string|Name $name): string
    {
        return $this->resolveName($name, new NameAsClassResolution());
    }

    /**
     * @template TReturn
     * @param NameResolution<TReturn, TTemplateMetadata> $resolution
     * @return TReturn
     */
    abstract public function resolveName(string|Name $name, NameResolution $resolution): mixed;
}
