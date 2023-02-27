<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\TypeReflection;

use PHP\ExtendedTypeSystem\Type\Type;

/**
 * @psalm-api
 */
final class TypeReflectorChain implements TypeReflector
{
    /**
     * @param iterable<TypeReflector> $typeReflectors
     */
    public function __construct(
        private readonly iterable $typeReflectors,
    ) {
    }

    public function reflectTypeFromString(string $type, ?string $scopeClass = null): ?Type
    {
        foreach ($this->typeReflectors as $typeReflector) {
            $reflectedType = $typeReflector->reflectTypeFromString($type, $scopeClass);

            if ($reflectedType !== null) {
                return $reflectedType;
            }
        }

        return null;
    }

    public function reflectFunctionParameterType(string|\Closure $function, string $parameter): ?Type
    {
        foreach ($this->typeReflectors as $typeReflector) {
            $type = $typeReflector->reflectFunctionParameterType($function, $parameter);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    public function reflectFunctionReturnType(string|\Closure $function): ?Type
    {
        foreach ($this->typeReflectors as $typeReflector) {
            $type = $typeReflector->reflectFunctionReturnType($function);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    public function reflectPropertyType(string $class, string $property): ?Type
    {
        foreach ($this->typeReflectors as $typeReflector) {
            $type = $typeReflector->reflectPropertyType($class, $property);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    public function reflectMethodParameterType(string $class, string $method, string $parameter): ?Type
    {
        foreach ($this->typeReflectors as $typeReflector) {
            $type = $typeReflector->reflectMethodParameterType($class, $method, $parameter);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    public function reflectMethodReturnType(string $class, string $method): ?Type
    {
        foreach ($this->typeReflectors as $typeReflector) {
            $type = $typeReflector->reflectMethodReturnType($class, $method);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }
}
