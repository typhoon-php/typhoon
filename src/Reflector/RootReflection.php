<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ChangeDetector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class RootReflection extends Reflection
{
    /**
     * @return non-empty-string
     */
    abstract public function getName(): string;

    abstract public function getChangeDetector(): ChangeDetector;
}
