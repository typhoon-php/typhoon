<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

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
