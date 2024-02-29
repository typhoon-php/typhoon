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
        private readonly string|false $version,
    ) {}

    public function changed(): bool
    {
        set_error_handler(static fn(): bool => true);

        try {
            return phpversion($this->extension) === $this->version;
        } finally {
            restore_error_handler();
        }
    }
}
