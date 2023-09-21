<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ChangeDetector;
use Typhoon\Reflection\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\ChangeDetector\PhpVersionChangeDetector;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\ReflectionContext;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\TypeReflection;
use Typhoon\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NativeReflectionReflector
{
    /**
     * @template T of object
     * @param \ReflectionClass<T> $class
     * @return ClassReflection<T>
     */
    public function reflectClass(\ReflectionClass $class, ReflectionContext $reflectionContext): ClassReflection
    {
        return new ClassReflection(
            reflectionContext: $reflectionContext,
            name: $class->name,
            changeDetector: $this->reflectChangeDetector($class),
            internal: $class->isInternal(),
            extensionName: $class->getExtensionName() ?: null,
            file: $class->getFileName() ?: null,
            startLine: $class->getStartLine() ?: null,
            endLine: $class->getEndLine() ?: null,
            docComment: $class->getDocComment() ?: null,
            templates: [],
            interface: $class->isInterface(),
            enum: $class->isEnum(),
            trait: $class->isTrait(),
            modifiers: $class->getModifiers(),
            anonymous: $class->isAnonymous(),
            parentType: $this->reflectParent($class),
            ownInterfaceTypes: array_map(
                static fn (string $interface): Type\NamedObjectType => types::object($interface),
                $class->getInterfaceNames(),
            ),
            ownProperties: $this->reflectOwnProperties($class),
            ownMethods: $this->reflectOwnMethods($class),
            reflectionClass: $class,
        );
    }

    private function reflectChangeDetector(\ReflectionClass $class): ChangeDetector
    {
        $file = $class->getFileName();

        if ($file) {
            return FileChangeDetector::fromFile($file);
        }

        if ($class->isInternal()) {
            return PhpVersionChangeDetector::fromExtension($class->getExtensionName() ?: null);
        }

        throw new ReflectionException();
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
     * @return list<PropertyReflection>
     */
    private function reflectOwnProperties(\ReflectionClass $class): array
    {
        $properties = [];

        foreach ($class->getProperties() as $property) {
            if ($property->class === $class->name) {
                /** @var non-empty-string */
                $name = $property->name;
                /** @var int-mask-of<PropertyReflection::IS_*> */
                $modifiers = $property->getModifiers();
                $properties[] = new PropertyReflection(
                    name: $name,
                    class: $class->name,
                    docComment: $property->getDocComment() ?: null,
                    hasDefaultValue: $property->hasDefaultValue(),
                    promoted: $property->isPromoted(),
                    modifiers: $modifiers,
                    type: $this->reflectType($property->getType(), $class->name),
                    startLine: null,
                    endLine: null,
                );
            }
        }

        return $properties;
    }

    /**
     * @return list<MethodReflection>
     */
    private function reflectOwnMethods(\ReflectionClass $class): array
    {
        $methods = [];

        foreach ($class->getMethods() as $method) {
            if ($method->class === $class->name) {
                /** @var non-empty-string */
                $name = $method->name;
                $methods[] = new MethodReflection(
                    class: $class->name,
                    name: $name,
                    templates: [],
                    modifiers: $method->getModifiers(),
                    docComment: $method->getDocComment() ?: null,
                    internal: $method->isInternal(),
                    extensionName: $method->getExtensionName() ?: null,
                    file: $method->getFileName() ?: null,
                    startLine: $method->getStartLine() ?: null,
                    endLine: $method->getEndLine() ?: null,
                    returnsReference: $method->returnsReference(),
                    generator: $method->isGenerator(),
                    parameters: $this->reflectParameters($method, $class->name),
                    returnType: $this->reflectType($method->getReturnType(), $class->name),
                );
            }
        }

        return $methods;
    }

    /**
     * @param ?class-string $class
     * @return list<ParameterReflection>
     */
    private function reflectParameters(\ReflectionFunctionAbstract $function, ?string $class): array
    {
        $parameters = [];
        /** @var callable-string|array{class-string, non-empty-string} */
        $reflectedFunction = $function instanceof \ReflectionMethod ? [$function->class, $function->name] : $function->name;

        foreach ($function->getParameters() as $parameter) {
            /** @var int<0, max> */
            $position = $parameter->getPosition();
            $parameters[] = new ParameterReflection(
                function: $reflectedFunction,
                position: $position,
                name: $parameter->name,
                passedByReference: $parameter->isPassedByReference(),
                defaultValueAvailable: $parameter->isDefaultValueAvailable(),
                optional: $parameter->isOptional(),
                variadic: $parameter->isVariadic(),
                promoted: $parameter->isPromoted(),
                type: $this->reflectType($parameter->getType(), $class),
                startLine: null,
                endLine: null,
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
     * @return ($reflectionType is null ? null : Type\Type)
     */
    private function reflectNativeType(?\ReflectionType $reflectionType, ?string $class): ?Type\Type
    {
        if ($reflectionType === null) {
            return null;
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            return types::union(...array_map(
                fn (\ReflectionType $child): Type\Type => $this->reflectNativeType($child, $class),
                $reflectionType->getTypes(),
            ));
        }

        if ($reflectionType instanceof \ReflectionIntersectionType) {
            return types::intersection(...array_map(
                fn (\ReflectionType $child): Type\Type => $this->reflectNativeType($child, $class),
                $reflectionType->getTypes(),
            ));
        }

        if (!$reflectionType instanceof \ReflectionNamedType) {
            throw new ReflectionException(sprintf('Unknown reflection type %s.', $reflectionType::class));
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
                ? throw new ReflectionException(sprintf(
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
