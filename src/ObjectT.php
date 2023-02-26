<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<object>
 */
final class ObjectT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitObject($this);
    }
}
