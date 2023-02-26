<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TValue
 * @implements Type<list<TValue>>
 */
final class ListT implements Type
{
    /**
     * @param Type<TValue> $valueType
     */
    public function __construct(
        public readonly Type $valueType = new MixedT(),
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitList($this);
    }
}
