<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ChangeDetector;

use Typhoon\Reflection\ChangeDetector;
use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @api
 */
final class PhpVersionChangeDetector implements ChangeDetector
{
    /**
     * @param ?non-empty-string $extension
     */
    private function __construct(
        private readonly ?string $extension,
        private readonly string $version,
    ) {}

    /**
     * @param ?non-empty-string $extension
     */
    public static function fromExtension(?string $extension): self
    {
        return new self(
            extension: $extension,
            version: exceptionally(static fn (): string|false => phpversion($extension)),
        );
    }

    public function changed(): bool
    {
        return phpversion($this->extension) === $this->version;
    }
}
