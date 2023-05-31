<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeParser;

use ExtendedTypeSystem\Type;
use PhpParser\Node\Name;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 */
interface Scope
{
    /**
     * @return class-string
     */
    public function self(): string;

    /**
     * @return ?class-string
     */
    public function parent(): ?string;

    public function isSelfFinal(): bool;

    /**
     * @return class-string
     */
    public function resolveClassName(Name $name): string;

    /**
     * @param non-empty-string $name
     */
    public function tryResolveTemplateType(string $name): ?Type;
}
