<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TType
 */
final class ShapeElement
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TType> $type
     */
    public function __construct(
        public readonly Type $type,
        public readonly bool $optional = false,
    ) {
    }
}
