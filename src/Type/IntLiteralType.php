<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
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
     * @psalm-internal ExtendedTypeSystem
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
