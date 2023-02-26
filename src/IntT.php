<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<int>
 */
final class IntT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitInt($this);
    }
}
