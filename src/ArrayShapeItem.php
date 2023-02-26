<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T
 */
final class ArrayShapeItem
{
    /**
     * @param Type<T> $type
     */
    public function __construct(
        public readonly Type $type,
        public readonly bool $optional = false,
    ) {
    }
}
