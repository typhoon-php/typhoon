<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
final class Resource
{
    /**
     * @var non-empty-string
     */
    public readonly string $file;

    /**
     * @var ?non-empty-string
     */
    public readonly ?string $extension;

    public function __construct(string $file, ?string $extension = null)
    {
        \assert($file !== '');
        \assert($extension !== '');

        $this->extension = $extension;
        $this->file = $file;
    }

    public function isInternal(): bool
    {
        return $this->extension !== null;
    }
}
