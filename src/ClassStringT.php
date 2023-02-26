<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<class-string>
 */
final class ClassStringT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitClassString($this);
    }
}
