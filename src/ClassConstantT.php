<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

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
