<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<false>
 */
enum FalseType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitFalse($this);
    }
}
