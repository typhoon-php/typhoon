<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeAlias;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TType
 * @extends TypeAlias<TType|null>
 */
final class NullableT extends TypeAlias
{
    /**
     * @param Type<TType> $type
     */
    public function __construct(
        public readonly Type $type,
    ) {
    }

    public function type(): Type
    {
        return new UnionT(new NullT(), $this->type);
    }
}
