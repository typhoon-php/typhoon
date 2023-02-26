<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

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
