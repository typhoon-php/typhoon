<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TInt of int
 * @implements Type<TInt>
 */
final class IntRangeType implements Type
{
    public readonly ?int $min;
    public readonly ?int $max;

    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     */
    public function __construct(
        ?int $min = null,
        ?int $max = null,
    ) {
        $this->max = $max;
        $this->min = $min;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntRange($this);
    }
}
