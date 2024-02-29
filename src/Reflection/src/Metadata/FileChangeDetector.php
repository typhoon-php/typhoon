<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class FileChangeDetector extends ChangeDetector
{
    /**
     * @param non-empty-string $file
     * @param non-empty-string $hash
     */
    protected function __construct(
        private readonly string $file,
        private readonly string $hash,
    ) {}

    public function changed(): bool
    {
        set_error_handler(static fn(): bool => true);

        try {
            return md5_file($this->file) !== $this->hash;
        } finally {
            restore_error_handler();
        }
    }
}
