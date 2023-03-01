<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TValue of int
 * @implements Type<TValue>
 */
final class IntLiteralT implements Type
{
    /**
     * @param TValue $value
     */
    public function __construct(
        public readonly int $value,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntLiteral($this);
    }
}
