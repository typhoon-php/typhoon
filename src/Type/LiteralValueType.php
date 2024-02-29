<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TValue of bool|int|float|string
 * @implements Type<TValue>
 */
final class LiteralValueType implements Type
{
    /**
     * @param TValue $value
     */
    public function __construct(
        private readonly bool|int|float|string $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->literalValue($this, $this->value);
    }
}
