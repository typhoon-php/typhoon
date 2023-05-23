<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\DeclarationParser;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use PhpParser\Node\Name;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\DeclarationParser
 */
final class MethodTypeScope implements TypeScope
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
        private readonly TypeScope $parentScope,
        private readonly string $name,
        private readonly bool $static,
        array $templateNames,
    ) {
        $this->templateNamesMap = array_fill_keys($templateNames, true);
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
        if (isset($this->templateNamesMap[$name])) {
            return types::methodTemplate($name, $this->self(), $this->name);
        }

        if ($this->static) {
            return null;
        }

        return $this->parentScope->tryResolveTemplateType($name);
    }
}
