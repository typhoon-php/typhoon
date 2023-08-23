<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

final class Resource
{
    /**
     * @var non-empty-string
     */
    public readonly string $file;

    /**
     * @var ?non-empty-string
     */
    public readonly ?string $extensionName;

    public function __construct(
        string $file,
        ?string $extensionName = null,
    ) {
        if ($file === '') {
            throw new \InvalidArgumentException('File name must not be empty.');
        }

        if ($extensionName === '') {
            throw new \InvalidArgumentException('Extension name must not be empty.');
        }

        $this->extensionName = $extensionName;
        $this->file = $file;
    }
}
