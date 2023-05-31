<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

/**
 * @api
 * @psalm-immutable
 */
final class Source
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $file = null,
        public readonly string $description = '',
    ) {
    }

    public static function fromFile(string $file, string $description = ''): self
    {
        return new self(
            code: file_get_contents($file),
            file: $file,
            description: $description,
        );
    }
}
