<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @api
 * @psalm-immutable
 */
final class PropertyDeclaration
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $private,
        public readonly TypeDeclaration $type,
    ) {
    }
}
