<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ChangeDetector;

use Typhoon\Reflection\ChangeDetector;
use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @api
 */
final class FileChangeDetector implements ChangeDetector
{
    /**
     * @param non-empty-string $file
     * @param non-empty-string $hash
     */
    private function __construct(
        private readonly string $file,
        private readonly string $hash,
    ) {}

    /**
     * @param non-empty-string $file
     */
    public static function fromFile(string $file): self
    {
        return new self($file, exceptionally(static fn (): string|false => md5_file($file)));
    }

    /**
     * @param non-empty-string $file
     */
    public static function fromContents(string $file, string $contents): self
    {
        return new self($file, md5($contents));
    }

    public function changed(): bool
    {
        try {
            return exceptionally(fn (): string|false => md5_file($this->file)) !== $this->hash;
        } catch (\Throwable) {
            return true;
        }
    }
}
