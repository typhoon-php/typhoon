<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TValue of literal-string
 * @implements Type<TValue>
 */
final class StringLiteralT implements Type
{
    /**
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
