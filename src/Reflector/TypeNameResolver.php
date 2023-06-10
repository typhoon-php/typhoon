<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Reflector;

use ExtendedTypeSystem\Reflection\NameResolution\NameResolver;
use ExtendedTypeSystem\Reflection\Reflector;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;

/**
 * @implements NameResolver<Type>
 */
final class TypeNameResolver implements NameResolver
{
    /**
     * @param list<Type> $templateArguments
     */
    public function __construct(
        private readonly Reflector $reflector,
        private readonly array $templateArguments = [],
    ) {
    }

    public function class(string $name): mixed
    {
        /** @var class-string $name */
        return types::object($name, ...$this->templateArguments);
    }

    public function static(string $class): mixed
    {
        /** @var class-string $class */
        return types::static($class, ...$this->templateArguments);
    }

    public function constant(string $name): mixed
    {
        return types::constant($name);
    }

    public function classTemplate(string $class, string $name): mixed
    {
        /** @var class-string $class */
        return types::classTemplate($class, $name);
    }

    public function methodTemplate(string $class, string $method, string $name): mixed
    {
        /** @var class-string $class */
        return types::methodTemplate($class, $method, $name);
    }

    public function classOrConstants(string $class, array $constants): mixed
    {
        if ($this->reflector->classExists($class)) {
            return types::object($class, ...$this->templateArguments);
        }

        foreach ($constants as $constant) {
            if (\defined($constant)) {
                return types::constant($constant);
            }
        }

        throw new \LogicException();
    }
}
