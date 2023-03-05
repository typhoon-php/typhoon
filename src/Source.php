<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class Source
{
    public function __construct(
        public readonly string $description,
        public readonly string $code,
    ) {
    }
}
