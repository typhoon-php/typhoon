<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

/**
 * @api
 */
final class FileChangeDetector implements ChangeDetector
{
    public const HASHING_ALGORITHM = 'xxh3';

    /**
     * @param non-empty-string $file
     * @param false|non-empty-string $xxh3
     */
    public function __construct(
        private readonly string $file,
        private readonly false|int $mtime,
        private readonly false|string $xxh3,
    ) {
        \assert(($mtime === false && $xxh3 === false) xor ($mtime !== false && $xxh3 !== false));
    }

    /**
     * @param non-empty-string $file
     */
    public static function fromFile(string $file): self
    {
        $mtime = @filemtime($file);

        if ($mtime === false) {
            throw new FileIsNotReadable($file);
        }

        $xxh3 = @hash_file(self::HASHING_ALGORITHM, $file);

        if ($xxh3 === false) {
            throw new FileIsNotReadable($file);
        }

        return new self($file, $mtime, $xxh3);
    }

    public function changed(): bool
    {
        return @filemtime($this->file) !== $this->mtime
            && @hash_file(self::HASHING_ALGORITHM, $this->file) !== $this->xxh3;
    }

    public function deduplicate(): array
    {
        $hash = \sprintf(
            '%s.%s.%s.%s',
            self::class,
            $this->file,
            $this->mtime === false ? 'false' : $this->mtime,
            $this->xxh3 === false ? 'false' : $this->xxh3,
        );

        return [$hash => $this];
    }
}
