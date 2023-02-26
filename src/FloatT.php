<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<float>
 */
final class FloatT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitFloat($this);
    }
}
