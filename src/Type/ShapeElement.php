<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
final class ShapeElement
{
    public function __construct(
        public readonly Type $type,
        public readonly bool $optional = false,
    ) {}
}
