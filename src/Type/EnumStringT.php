<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<enum-string>
 */
final class EnumStringT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitEnumString($this);
    }
}
