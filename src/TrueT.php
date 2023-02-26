<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<true>
 */
final class TrueT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitTrue($this);
    }
}
