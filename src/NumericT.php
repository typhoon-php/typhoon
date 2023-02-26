<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<numeric>
 */
final class NumericT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNumeric($this);
    }
}
