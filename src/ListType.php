<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TValue
 * @implements Type<list<TValue>>
 */
final class ListType implements Type
{
    /**
     * @param Type<TValue> $valueType
     */
    public function __construct(
        private readonly Type $valueType,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->list($this, $this->valueType);
    }
}
