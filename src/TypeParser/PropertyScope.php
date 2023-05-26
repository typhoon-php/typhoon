<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeParser;

use ExtendedTypeSystem\Type;
use PhpParser\Node\Name;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class PropertyScope implements Scope
{
    public function __construct(
        private readonly ClassLikeScope $classScope,
        private readonly bool $static,
    ) {
    }

    public function self(): string
    {
        return $this->classScope->self();
    }

    public function parent(): ?string
    {
        return $this->classScope->parent();
    }

    public function isSelfFinal(): bool
    {
        return $this->classScope->isSelfFinal();
    }

    public function resolveClassName(Name $name): string
    {
        return $this->classScope->resolveClassName($name);
    }

    public function tryResolveTemplateType(string $name): ?Type
    {
        if ($this->static) {
            return null;
        }

        return $this->classScope->tryResolveTemplateType($name);
    }
}
