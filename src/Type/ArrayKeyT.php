<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeAlias;

/**
 * @psalm-api
 * @psalm-immutable
 * @extends TypeAlias<array-key>
 */
final class ArrayKeyT extends TypeAlias
{
    public function type(): Type
    {
        return new UnionT(new IntT(), new StringT());
    }
}
