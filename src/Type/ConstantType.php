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
     * @var non-empty-string
     */
    public readonly string $constant;

    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param non-empty-string $constant
     */
    public function __construct(
        string $constant,
    ) {
        $this->constant = $constant;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitConstant($this);
    }
}
