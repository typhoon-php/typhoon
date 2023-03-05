<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\Type;
use PhpParser\Node\Name;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\TypeReflector
 */
final class PropertyScope implements Scope
{
    public function __construct(
        private readonly Scope $parentScope,
        private readonly bool $static,
    ) {
    }

    public function self(): string
    {
        return $this->parentScope->self();
    }

    public function parent(): string
    {
        return $this->parentScope->parent();
    }

    public function isSelfFinal(): bool
    {
        return $this->parentScope->isSelfFinal();
    }

    public function resolveClassName(Name $name): Name
    {
        return $this->parentScope->resolveClassName($name);
    }

    public function tryResolveTemplateType(string $name): ?Type
    {
        if ($this->static) {
            return null;
        }

        return $this->parentScope->tryResolveTemplateType($name);
    }
}
