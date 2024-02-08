<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NativeReflector;

use Typhoon\Reflection\Exception\DefaultReflectionException;
use Typhoon\Reflection\Metadata\AttributeMetadata;
use Typhoon\Reflection\Metadata\ChangeDetector;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\Metadata\TypeMetadata;
use Typhoon\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NativeReflector
{
    /**
     * @template T of object
     * @param \ReflectionClass<T> $class
     * @return ClassMetadata<T>
     */
    public function reflectClass(\ReflectionClass $class): ClassMetadata
    {
        return new ClassMetadata(
            changeDetector: ChangeDetector::fromReflection($class),
            name: $class->name,
            modifiers: $class->getModifiers(),
            internal: $class->isInternal(),
            extension: $class->getExtensionName(),
            file: $class->getFileName(),
            startLine: $class->getStartLine(),
            endLine: $class->getEndLine(),
            docComment: $class->getDocComment(),
            attributes: $this->reflectAttributes($class->getAttributes()),
            interface: $class->isInterface(),
            enum: $class->isEnum(),
            trait: $class->isTrait(),
            anonymous: $class->isAnonymous(),
            parentType: $this->reflectParent($class),
            ownInterfaceTypes: array_map(
                static fn(string $interface): Type\NamedObjectType => types::object($interface),
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
     * @return list<PropertyMetadata>
     */
    private function reflectOwnProperties(\ReflectionClass $class): array
    {
        $properties = [];

        foreach ($class->getProperties() as $property) {
            if ($property->class === $class->name) {
                /** @var non-empty-string */
                $name = $property->name;
                /** @var int-mask-of<\ReflectionProperty::IS_*> */
                $modifiers = $property->getModifiers();
                $properties[] = new PropertyMetadata(
                    name: $name,
                    class: $property->class,
                    modifiers: $modifiers,
                    type: $this->reflectType($property->getType(), $class->name),
                    docComment: $property->getDocComment(),
                    hasDefaultValue: $property->hasDefaultValue(),
                    promoted: $property->isPromoted(),
                    attributes: $this->reflectAttributes($property->getAttributes()),
                );
            }
        }

        return $properties;
    }

    /**
     * @return list<MethodMetadata>
     */
    private function reflectOwnMethods(\ReflectionClass $class): array
    {
        $methods = [];

        foreach ($class->getMethods() as $method) {
            if ($method->class === $class->name) {
                $methods[] = new MethodMetadata(
                    name: $method->name,
                    class: $method->class,
                    modifiers: $method->getModifiers(),
                    parameters: $this->reflectParameters($method, $class->name),
                    returnType: $this->reflectType($method->getReturnType(), $class->name),
                    docComment: $method->getDocComment(),
                    internal: $method->isInternal(),
                    extension: $method->getExtensionName(),
                    file: $method->getFileName(),
                    startLine: $method->getStartLine(),
                    endLine: $method->getEndLine(),
                    returnsReference: $method->returnsReference(),
                    generator: $method->isGenerator(),
                    deprecated: $method->isDeprecated(),
                    attributes: $this->reflectAttributes($method->getAttributes()),
                );
            }
        }

        return $methods;
    }

    /**
     * @param ?class-string $class
     * @return list<ParameterMetadata>
     */
    private function reflectParameters(\ReflectionFunctionAbstract $function, ?string $class): array
    {
        $parameters = [];
        /** @var non-empty-string */
        $functionOrMethod = $function->name;

        foreach ($function->getParameters() as $parameter) {
            $parameters[] = new ParameterMetadata(
                position: $parameter->getPosition(),
                name: $parameter->name,
                class: $parameter->getDeclaringClass()?->name,
                functionOrMethod: $functionOrMethod,
                type: $this->reflectType($parameter->getType(), $class),
                passedByReference: $parameter->isPassedByReference(),
                defaultValueAvailable: $parameter->isDefaultValueAvailable(),
                optional: $parameter->isOptional(),
                variadic: $parameter->isVariadic(),
                promoted: $parameter->isPromoted(),
                attributes: $this->reflectAttributes($parameter->getAttributes()),
            );
        }

        return $parameters;
    }

    /**
     * @param array<\ReflectionAttribute> $reflectionAttributes
     * @return list<AttributeMetadata>
     */
    private function reflectAttributes(array $reflectionAttributes): array
    {
        $attributes = [];

        foreach (array_values($reflectionAttributes) as $position => $attribute) {
            /** @var class-string */
            $name = $attribute->getName();
            $attributes[] = new AttributeMetadata(
                name: $name,
                position: $position,
                target: $attribute->getTarget(),
                repeated: $attribute->isRepeated(),
            );
        }

        return $attributes;
    }

    /**
     * @param ?class-string $class
     */
    private function reflectType(?\ReflectionType $type, ?string $class): TypeMetadata
    {
        return TypeMetadata::create(native: $type === null ? null : $this->reflectNativeType($type, $class));
    }

    /**
     * @param ?class-string $class
     */
    private function reflectNativeType(\ReflectionType $reflectionType, ?string $class): Type\Type
    {
        if ($reflectionType instanceof \ReflectionUnionType) {
            return types::union(...array_map(
                fn(\ReflectionType $child): Type\Type => $this->reflectNativeType($child, $class),
                $reflectionType->getTypes(),
            ));
        }

        if ($reflectionType instanceof \ReflectionIntersectionType) {
            return types::intersection(...array_map(
                fn(\ReflectionType $child): Type\Type => $this->reflectNativeType($child, $class),
                $reflectionType->getTypes(),
            ));
        }

        if (!$reflectionType instanceof \ReflectionNamedType) {
            throw new DefaultReflectionException(sprintf('Unknown reflection type %s.', $reflectionType::class));
        }

        $name = $reflectionType->getName();

        if ($name === 'self') {
            if ($class === null) {
                throw new \LogicException('Cannot use type "self" outside of class scope.');
            }

            return types::object($class);
        }

        if ($name === 'parent') {
            if ($class === null) {
                throw new \LogicException('Cannot use type "parent" outside of class scope.');
            }

            $parent = get_parent_class($class);

            if ($parent === false) {
                throw new \LogicException(sprintf('Cannot use type "parent": class %s does not have a parent.', $class));
            }

            return types::object($parent);
        }

        if ($name === 'static') {
            if ($class === null) {
                throw new \LogicException('Cannot use type "static" outside of class scope.');
            }

            return types::static($class);
        }

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
            'callable' => types::callable(),
            'iterable' => types::iterable(),
            'resource' => types::resource,
            'mixed' => types::mixed,
            default => $reflectionType->isBuiltin()
                ? throw new DefaultReflectionException(sprintf(
                    '%s with name "%s" is not supported.',
                    \ReflectionNamedType::class,
                    $name,
                ))
                : types::object($reflectionType->getName()),
        };

        if ($reflectionType->allowsNull() && $name !== 'null' && $name !== 'mixed') {
            return types::nullable($type);
        }

        return $type;
    }
}
