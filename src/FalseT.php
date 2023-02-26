<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<false>
 */
final class FalseT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitFalse($this);
    }
}
