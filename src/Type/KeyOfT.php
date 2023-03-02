<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class KeyOfT implements Type
{
    public function __construct(
        public readonly Type $type,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitKeyOf($this);
    }
}
