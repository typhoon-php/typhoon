<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class FriendlyReflection
{
    /**
     * @param static $parent
     */
    abstract protected function toChildOf(self $parent): static;
}
