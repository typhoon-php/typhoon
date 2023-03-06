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
        public readonly null|int|string $key,
        public readonly bool $optional,
        public readonly Type $type,
    ) {
    }
}
