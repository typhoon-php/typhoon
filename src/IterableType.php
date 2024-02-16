<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TKey
 * @template-covariant TValue
 * @implements Type<iterable<TKey, TValue>>
 */
final class IterableType implements Type
{
    /**
     * @var Type<TKey>
     */
    public readonly Type $keyType;

    /**
     * @var Type<TValue>
     */
    public readonly Type $valueType;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     */
    public function __construct(
        Type $keyType = MixedType::type,
        Type $valueType = MixedType::type,
    ) {
        $this->valueType = $valueType;
        $this->keyType = $keyType;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIterable($this);
    }
}
