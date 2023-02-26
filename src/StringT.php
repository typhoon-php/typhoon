<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<string>
 */
final class StringT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitString($this);
    }
}
