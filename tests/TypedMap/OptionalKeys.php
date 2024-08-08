<?php

declare(strict_types=1);

namespace Typhoon\TypedMap;

/**
 * @implements OptionalKey<mixed>
 */
enum OptionalKeys implements OptionalKey
{
    public const DEFAULT = '129afde0-d3d2-4b36-b073-b06fecb6d775';
    case A;
    case B;

    public function default(TypedMap $map): mixed
    {
        return self::DEFAULT;
    }
}
