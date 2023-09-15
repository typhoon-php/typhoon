<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ChangeDetector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class Resource
{
    /**
     * @param non-empty-string $file
     * @param ?non-empty-string $extension
     */
    public function __construct(
        public readonly string $file,
        public readonly ?string $extension,
        public readonly ChangeDetector $changeDetector,
    ) {}

    public function isInternal(): bool
    {
        return $this->extension !== null;
    }
}
