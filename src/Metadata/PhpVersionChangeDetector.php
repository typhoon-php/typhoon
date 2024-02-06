<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpVersionChangeDetector extends ChangeDetector
{
    /**
     * @param ?non-empty-string $extension
     */
    protected function __construct(
        private readonly ?string $extension,
        private readonly string $version,
    ) {}

    public function changed(): bool
    {
        return phpversion($this->extension) === $this->version;
    }
}
