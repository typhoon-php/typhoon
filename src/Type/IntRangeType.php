<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TInt of int
 * @implements Type<TInt>
 */
final class IntRangeType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     */
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
