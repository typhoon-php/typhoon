<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Metadata\ChangeDetector;

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

    private ?ChangeDetector $changeDetector = null;

    public function __construct(string $file, string|false $extension = false)
    {
        \assert($file !== '');
        \assert($extension !== '');

        $this->extension = $extension;
        $this->file = $file;
    }

    public function contents(): string
    {
        if ($this->contents !== null) {
            return $this->contents;
        }

        $contents = file_get_contents($this->file);

        if ($contents === false) {
            throw new \RuntimeException();
        }

        return $this->contents = $contents;
    }

    public function changeDetector(): ChangeDetector
    {
        return $this->changeDetector ??= ChangeDetector::fromFileContents($this->file, $this->contents);
    }

    public function isInternal(): bool
    {
        return $this->extension !== false;
    }
}
