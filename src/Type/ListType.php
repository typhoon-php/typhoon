<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TValue
 * @implements Type<list<TValue>>
 */
final class ListType implements Type
{
    /**
     * @var Type<TValue>
     */
    public readonly Type $valueType;

    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TValue> $valueType
     */
    public function __construct(
        Type $valueType = MixedType::type,
    ) {
        $this->valueType = $valueType;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitList($this);
    }
}
