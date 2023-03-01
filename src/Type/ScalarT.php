<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeAlias;

/**
 * @psalm-api
 * @psalm-immutable
 * @extends TypeAlias<scalar>
 */
final class ScalarT extends TypeAlias
{
    public function type(): Type
    {
        return new UnionT(new BoolT(), new IntT(), new FloatT(), new StringT());
    }
}
