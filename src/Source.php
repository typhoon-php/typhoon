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
        public readonly string $description,
    ) {
    }

    public static function fromFile(string $file, string $via = ''): self
    {
        $code = @file_get_contents($file);

        if ($code === false) {
            throw new \RuntimeException(sprintf('Failed to open file %s.', $file));
        }

        $realpath = realpath($file);

        return new self($code, $via ? sprintf('%s (via %s)', $realpath, $via) : $realpath);
    }
}
