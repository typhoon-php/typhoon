<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T of object
 * @implements Type<class-string<T>>
 */
final class NamedClassStringT implements Type
{
    /**
     * @param Type<T> $type
     */
    public function __construct(
        public readonly Type $type,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNamedClassString($this);
    }
}
