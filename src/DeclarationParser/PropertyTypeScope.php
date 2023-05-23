<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\DeclarationParser;

use ExtendedTypeSystem\Type;
use PhpParser\Node\Name;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\DeclarationParser
 */
final class PropertyTypeScope implements TypeScope
{
    public function __construct(
        private readonly TypeScope $parentScope,
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
