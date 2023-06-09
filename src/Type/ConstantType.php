<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TConstant
 * @implements Type<TConstant>
 */
final class ConstantType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param non-empty-string $constant
     */
    public function __construct(
        public readonly string $constant,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitConstant($this);
    }
}
