<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ChangeDetector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface RootReflection
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    public function getChangeDetector(): ChangeDetector;
}
