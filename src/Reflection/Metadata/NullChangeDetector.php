<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NullChangeDetector extends ChangeDetector
{
    protected function __construct() {}

    public function changed(): bool
    {
        return true;
    }
}
