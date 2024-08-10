<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

/**
 * @api
 */
final class PhpVersionChangeDetector implements ChangeDetector
{
    public function __construct(
        private readonly int $version = \PHP_VERSION_ID,
    ) {}

    public function changed(): bool
    {
        return $this->version !== \PHP_VERSION_ID;
    }

    public function deduplicate(): array
    {
        return [self::class . '.' . $this->version => $this];
    }
}
