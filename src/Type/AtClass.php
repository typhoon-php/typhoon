<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class AtClass
{
    /**
     * @param class-string $class
     */
    public function __construct(
        public readonly string $class,
    ) {
    }
}
