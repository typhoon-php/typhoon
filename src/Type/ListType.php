<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TValue
 * @implements Type<list<TValue>>
 */
final class ListType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TValue> $valueType
     */
    public function __construct(
        public readonly Type $valueType = MixedType::type,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitList($this);
    }
}
