<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\Type;
use PhpParser\Node\Name;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\TypeReflector
 */
interface Scope
{
    /**
     * @return class-string
     */
    public function self(): string;

    /**
     * @return class-string
     */
    public function parent(): string;

    public function isSelfFinal(): bool;

    public function resolveClassName(Name $name): Name;

    /**
     * @param non-empty-string $name
     */
    public function tryResolveTemplateType(string $name): ?Type;
}
