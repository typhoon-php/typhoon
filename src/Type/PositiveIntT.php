<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeAlias;

/**
 * @psalm-api
 * @psalm-immutable
 * @extends TypeAlias<positive-int>
 */
final class PositiveIntT extends TypeAlias
{
    public function type(): Type
    {
        /** @var IntRangeT<positive-int> */
        return new IntRangeT(min: 1);
    }
}
