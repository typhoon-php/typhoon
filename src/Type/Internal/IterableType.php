<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @psalm-immutable
 * @template-covariant TKey
 * @template-covariant TValue
 * @implements Type<iterable<TKey, TValue>>
 */
final class IterableType implements Type
{
    /**
     * @param Type<TKey> $key
     * @param Type<TValue> $value
     */
    public function __construct(
        private readonly Type $key,
        private readonly Type $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->iterable($this, $this->key, $this->value);
    }
}
