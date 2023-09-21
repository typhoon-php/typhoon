<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TValue of float
 * @implements Type<TValue>
 */
final class FloatLiteralType implements Type
{
    /**
     * @var TValue
     */
    public readonly float $value;

    /**
     * @internal
     * @psalm-internal Typhoon
     * @param TValue $value
     */
    public function __construct(
        float $value,
    ) {
        $this->value = $value;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitFloatLiteral($this);
    }
}
