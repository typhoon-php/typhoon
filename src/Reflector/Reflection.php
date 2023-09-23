<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ReflectionContext;
use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class Reflection
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected readonly ReflectionContext $reflectionContext;

    /**
     * @psalm-suppress PossiblyUnusedMethod, InaccessibleProperty, RedundantPropertyInitializationCheck
     */
    final protected function load(ReflectionContext $reflectionContext): void
    {
        if (!isset($this->reflectionContext)) {
            $this->reflectionContext = $reflectionContext;
        } elseif ($this->reflectionContext !== $reflectionContext) {
            throw new ReflectionException();
        }

        foreach ($this->childReflections() as $reflection) {
            $reflection->load($reflectionContext);
        }
    }

    /**
     * @return iterable<self>
     */
    abstract protected function childReflections(): iterable;
}
