<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ChangeDetector;

use ExtendedTypeSystem\Reflection\ChangeDetector;

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
    ) {
    }

    /**
     * @param non-empty-string $file
     */
    public static function fromFile(string $file): self
    {
        return new self($file, @md5_file($file) ?: throw new \RuntimeException());
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
        set_error_handler(static fn (): bool => true);
        $currentHash = md5_file($this->file);
        restore_error_handler();

        return $currentHash !== $this->hash;
    }
}
