<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use function Typhoon\Reflection\Exceptionally\exceptionally;

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
        try {
            return exceptionally(fn(): string|false => md5_file($this->file)) !== $this->hash;
        } catch (\Throwable) {
            return true;
        }
    }
}
