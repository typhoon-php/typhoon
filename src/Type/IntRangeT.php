<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T of int
 * @implements Type<T>
 */
final class IntRangeT implements Type
{
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntRange($this);
    }
}
