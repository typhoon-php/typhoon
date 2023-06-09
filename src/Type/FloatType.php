<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @implements Type<float>
 */
enum FloatType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitFloat($this);
    }
}
