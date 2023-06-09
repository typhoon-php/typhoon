<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 */
final class ShapeElement
{
    /**
     * @var Type<TType>
     */
    public readonly Type $type;
    public readonly bool $optional;

    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TType> $type
     */
    public function __construct(
        Type $type,
        bool $optional = false,
    ) {
        $this->optional = $optional;
        $this->type = $type;
    }
}
