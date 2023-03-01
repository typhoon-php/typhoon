<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T
 * @implements Type<T>
 */
final class ConstantT implements Type
{
    public function __construct(
        public readonly string $constant,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitConstant($this);
    }
}
