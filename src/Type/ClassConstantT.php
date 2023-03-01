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
final class ClassConstantT implements Type
{
    /**
     * @param class-string $class
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
