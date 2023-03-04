<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TValue of literal-string
 * @implements Type<TValue>
 */
final class StringLiteralType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param TValue $value
     */
    public function __construct(
        public readonly string $value,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitStringLiteral($this);
    }
}
