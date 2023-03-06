<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<non-positive-int>
 */
enum NonPositiveIntType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNonPositiveInt($this);
    }
}
