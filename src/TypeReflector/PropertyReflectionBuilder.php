<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class PropertyReflectionBuilder
{
    public readonly TypeReflectionBuilder $type;
    public bool $inheritable = true;

    public function __construct()
    {
        $this->type = new TypeReflectionBuilder();
    }

    public function inheritable(bool $inheritable): self
    {
        $this->inheritable = $inheritable;

        return $this;
    }
}
