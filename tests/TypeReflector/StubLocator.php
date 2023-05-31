<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector
{
    use ExtendedTypeSystem\Reflection\ClassLocator;
    use ExtendedTypeSystem\Reflection\Source;
    use N;

    final class StubLocator implements ClassLocator
    {
        private const CLASSES = [
            N\A::class,
            N\B::class,
            N\X::class,
        ];

        private readonly Source $source;

        public function __construct(
            string $code,
        ) {
            $this->source = new Source($code, self::class);
        }

        public function locateClass(string $class): ?Source
        {
            if (\in_array($class, self::CLASSES, true)) {
                return $this->source;
            }

            return null;
        }
    }
}

namespace N
{
    final class A
    {
    }

    final class B
    {
    }

    final class X
    {
    }
}
