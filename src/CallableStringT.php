<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<callable-string>
 */
final class CallableStringT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitCallableString($this);
    }
}
