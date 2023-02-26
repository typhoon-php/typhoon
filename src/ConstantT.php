<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

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
