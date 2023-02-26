<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @extends TypeAlias<numeric-string>
 */
final class NumericStringT extends TypeAlias
{
    public function type(): Type
    {
        /** @var IntersectionT<numeric-string> */
        return new IntersectionT(new StringT(), new NumericT());
    }
}
