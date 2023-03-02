<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TClassConstant
 * @implements Type<TClassConstant>
 */
final class ClassConstantT implements Type
{
    /**
     * @param class-string $class
     * @param non-empty-string $constant
     */
    public function __construct(
        public readonly string $class,
        public readonly string $constant,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitClassConstant($this);
    }
}
