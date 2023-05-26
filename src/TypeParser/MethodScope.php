<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeParser;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use PhpParser\Node\Name;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class MethodScope implements Scope
{
    /**
     * @var array<non-empty-string, true>
     */
    private readonly array $templateNamesMap;

    /**
     * @param non-empty-string $name
     * @param list<non-empty-string> $templateNames
     */
    public function __construct(
        private readonly ClassLikeScope $classScope,
        private readonly string $name,
        private readonly bool $static,
        array $templateNames,
    ) {
        $this->templateNamesMap = array_fill_keys($templateNames, true);
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
        if (isset($this->templateNamesMap[$name])) {
            return types::methodTemplate($name, $this->self(), $this->name);
        }

        if ($this->static) {
            return null;
        }

        return $this->classScope->tryResolveTemplateType($name);
    }
}
