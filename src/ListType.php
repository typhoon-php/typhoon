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
     * @param Type<TValue> $value
     */
    public function __construct(
        private readonly Type $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->list($this, $this->value);
    }
}
