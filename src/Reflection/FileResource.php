<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Exception\FileNotReadable;
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

    /**
     * @deprecated will be removed in 0.4.0
     * @throws FileNotReadable
     */
    public function changeDetector(): ChangeDetector
    {
        trigger_deprecation('typhoon/reflection', '0.3.1', 'Method %s() is deprecated and will be removed on 0.4.0.', __METHOD__);

        return $this->changeDetector ??= ChangeDetector::fromFileContents($this->file, $this->contents());
    }

    public function isInternal(): bool
    {
        return $this->extension !== false;
    }
}
