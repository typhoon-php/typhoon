<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
final class Parameter
{
    public function __construct(
        public readonly Type $type = types::mixed,
        public readonly bool $hasDefault = false,
        public readonly bool $variadic = false,
        public readonly bool $byReference = false,
    ) {}
}
