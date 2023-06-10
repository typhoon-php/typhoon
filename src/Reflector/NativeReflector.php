<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Reflector;

use ExtendedTypeSystem\Reflection\ClassReflection;
use ExtendedTypeSystem\Reflection\MethodReflection;
use ExtendedTypeSystem\Reflection\ParameterReflection;
use ExtendedTypeSystem\Reflection\PropertyReflection;
use ExtendedTypeSystem\Reflection\TypeReflection;
use ExtendedTypeSystem\Reflection\Visibility;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;

final class NativeReflector
{
    public function reflectClass(\ReflectionClass $class): ClassReflection
    {
        return new ClassReflection(
            name: $class->name,
            templates: [],
            final: $class->isFinal(),
            abstract: $class->isAbstract(),
            readonly: \PHP_VERSION_ID > 80200 && $class->isReadOnly(),
            parent: $this->reflectParent($class),
            interfaces: array_map(
                static fn (string $interface): Type\NamedObjectType => types::object($interface),
                $class->getInterfaceNames(),
            ),
            ownProperties: $this->reflectOwnProperties($class),
            ownMethods: $this->reflectOwnMethods($class),
        );
    }

    private function reflectParent(\ReflectionClass $class): ?Type\NamedObjectType
    {
        $parentClass = $class->getParentClass();

        if ($parentClass === false) {
            return null;
        }

        return types::object($parentClass->name);
    }

    /**
     * @return array<non-empty-string, PropertyReflection>
     */
    private function reflectOwnProperties(\ReflectionClass $class): array
    {
        $properties = [];

        foreach ($class->getProperties() as $property) {
            if ($property->class === $class->name) {
                /** @var non-empty-string */
                $name = $property->name;
                $properties[$name] = $this->reflectProperty($property, $class->name);
            }
        }

        return $properties;
    }

    /**
     * @param class-string $class
     */
    private function reflectProperty(\ReflectionProperty $property, string $class): PropertyReflection
    {
        /** @var non-empty-string */
        $name = $property->name;

        return new PropertyReflection(
            name: $name,
            static: $property->isStatic(),
            promoted: $property->isPromoted(),
            hasDefaultValue: $property->hasDefaultValue(),
            readonly: $property->isReadOnly(),
            visibility: match (true) {
                $property->isPrivate() => Visibility::PRIVATE,
                $property->isProtected() => Visibility::PROTECTED,
                default => Visibility::PUBLIC,
            },
            type: $this->reflectType($property->getType(), $class),
        );
    }

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    private function reflectOwnMethods(\ReflectionClass $class): array
    {
        $methods = [];

        foreach ($class->getMethods() as $method) {
            if ($method->class === $class->name) {
                /** @var non-empty-string */
                $name = $method->name;
                $methods[$name] = $this->reflectMethod($method, $class->name);
            }
        }

        return $methods;
    }

    /**
     * @param class-string $class
     */
    private function reflectMethod(\ReflectionMethod $method, string $class): MethodReflection
    {
        /** @var non-empty-string */
        $name = $method->name;

        return new MethodReflection(
            name: $name,
            static: $method->isStatic(),
            final: $method->isFinal(),
            abstract: $method->isAbstract(),
            visibility: match (true) {
                $method->isPrivate() => Visibility::PRIVATE,
                $method->isProtected() => Visibility::PROTECTED,
                default => Visibility::PUBLIC,
            },
            templates: [],
            parameters: $this->reflectParameters($method->getParameters(), $class),
            returnType: $this->reflectType($method->getReturnType(), $class),
        );
    }

    /**
     * @param array<\ReflectionParameter> $reflectionParameters
     * @param ?class-string $class
     * @return array<non-empty-string, ParameterReflection>
     */
    private function reflectParameters(array $reflectionParameters, ?string $class): array
    {
        $parameters = [];

        foreach ($reflectionParameters as $reflectionParameter) {
            /** @var int<0, max> */
            $position = $reflectionParameter->getPosition();
            $parameters[$reflectionParameter->name] = new ParameterReflection(
                position: $position,
                name: $reflectionParameter->name,
                promoted: $reflectionParameter->isPromoted(),
                variadic: $reflectionParameter->isVariadic(),
                hasDefaultValue: $reflectionParameter->isDefaultValueAvailable(),
                type: $this->reflectType($reflectionParameter->getType(), $class),
            );
        }

        return $parameters;
    }

    /**
     * @param ?class-string $class
     */
    private function reflectType(?\ReflectionType $type, ?string $class): TypeReflection
    {
        return new TypeReflection(
            native: $this->reflectNativeType($type, $class),
            phpDoc: null,
        );
    }

    /**
     * @param ?class-string $class
     * @return ($reflectionType is null ? null : Type)
     */
    private function reflectNativeType(?\ReflectionType $reflectionType, ?string $class): ?Type
    {
        if ($reflectionType === null) {
            return null;
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            return types::union(...array_map(
                fn (\ReflectionType $child): Type => $this->reflectNativeType($child, $class),
                $reflectionType->getTypes(),
            ));
        }

        if ($reflectionType instanceof \ReflectionIntersectionType) {
            return types::intersection(...array_map(
                fn (\ReflectionType $child): Type => $this->reflectNativeType($child, $class),
                $reflectionType->getTypes(),
            ));
        }

        if (!$reflectionType instanceof \ReflectionNamedType) {
            throw new \LogicException();
        }

        $name = $reflectionType->getName();

        /** @psalm-suppress ArgumentTypeCoercion */
        $type = match ($name) {
            'never' => types::never,
            'void' => types::void,
            'null' => types::null,
            'true' => types::true,
            'false' => types::false,
            'bool' => types::bool,
            'int' => types::int,
            'float' => types::float,
            'string' => types::string,
            'array' => types::array(),
            'object' => types::object,
            'Closure' => types::closure(),
            'self' => types::object($class ?? throw new \LogicException()),
            'parent' => types::object(get_parent_class($class ?? throw new \LogicException()) ?: throw new \LogicException()),
            'static' => types::static($class ?? throw new \LogicException()),
            'callable' => types::callable(),
            'iterable' => types::iterable(),
            'resource' => types::resource,
            'mixed' => types::mixed,
            default => types::object($name),
        };

        if ($reflectionType->allowsNull() && $name !== 'null' && $name !== 'mixed') {
            return types::nullable($type);
        }

        return $type;
    }
}
