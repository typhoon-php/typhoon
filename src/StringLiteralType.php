<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TValue of string
 * @implements Type<TValue>
 */
final class StringLiteralType implements Type
{
    /**
     * @var TValue
     */
    public readonly string $value;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param TValue $value
     */
    public function __construct(
        string $value,
    ) {
        $this->value = $value;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitStringLiteral($this);
    }
}
