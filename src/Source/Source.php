<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Source;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class Source
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $filename = null,
    ) {
    }
}
