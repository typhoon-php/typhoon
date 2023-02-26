<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<never>
 */
final class NeverT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNever($this);
    }
}
