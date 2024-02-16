<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TValue of int
 * @implements Type<TValue>
 */
final class IntLiteralType implements Type
{
    /**
     * @var TValue
     */
    public readonly int $value;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param TValue $value
     */
    public function __construct(
        int $value,
    ) {
        $this->value = $value;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntLiteral($this);
    }
}
