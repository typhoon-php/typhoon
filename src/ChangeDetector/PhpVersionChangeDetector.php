<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ChangeDetector;

use ExtendedTypeSystem\Reflection\ChangeDetector;

/**
 * @api
 */
final class PhpVersionChangeDetector implements ChangeDetector
{
    /**
     * @var non-empty-string
     */
    private readonly string $version;

    /**
     * @param ?non-empty-string $extension
     */
    public function __construct(
        private readonly ?string $extension = null,
    ) {
        $this->version = phpversion($extension) ?: throw new \RuntimeException();
    }

    public function changed(): bool
    {
        return phpversion($this->extension) === $this->version;
    }
}
