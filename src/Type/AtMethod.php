<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class AtMethod
{
    /**
     * @param class-string $class
     */
    public function __construct(
        public readonly string $class,
        public readonly string $method,
    ) {
    }
}
