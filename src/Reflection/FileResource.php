<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Exception\FileNotReadable;

/**
 * @api
 */
final class FileResource
{
    /**
     * @var non-empty-string
     */
    public readonly string $file;

    /**
     * @var non-empty-string|false
     */
    public readonly string|false $extension;

    private ?string $contents = null;

    public function __construct(string $file, string|false $extension = false)
    {
        \assert($file !== '', 'File must not be empty');
        \assert($extension !== '', 'Extension must not be empty');

        $this->extension = $extension;
        $this->file = $file;
    }

    /**
     * @throws FileNotReadable
     */
    public function contents(): string
    {
        if ($this->contents !== null) {
            return $this->contents;
        }

        $contents = @file_get_contents($this->file);

        if ($contents === false) {
            throw new FileNotReadable($this->file);
        }

        return $this->contents = $contents;
    }

    public function isInternal(): bool
    {
        return $this->extension !== false;
    }
}
