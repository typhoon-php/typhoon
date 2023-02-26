<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<trait-string>
 */
final class TraitStringT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitTraitString($this);
    }
}
