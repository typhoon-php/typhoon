<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
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
     * @psalm-internal Typhoon\Type
     * @param Type<TValue> $valueType
     */
    public function __construct(
        Type $valueType = MixedType::Type,
    ) {
        $this->valueType = $valueType;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitList($this);
    }
}
