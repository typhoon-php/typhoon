<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TValue of bool|int|float|string
 * @implements Type<TValue>
 */
final class LiteralType implements Type
{
    /**
     * @var TValue
     */
    public readonly bool|int|float|string $value;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param TValue $value
     */
    public function __construct(
        bool|int|float|string $value,
    ) {
        $this->value = $value;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitLiteral($this);
    }
}
