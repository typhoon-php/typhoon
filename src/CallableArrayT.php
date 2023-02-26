<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<callable-array>
 */
final class CallableArrayT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitCallableArray($this);
    }
}
