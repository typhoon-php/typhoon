<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NativeReflector;

use Typhoon\Reflection\Exception\DefaultReflectionException;
use Typhoon\Reflection\Metadata\AttributeMetadata;
use Typhoon\Reflection\Metadata\ChangeDetector;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\InheritedName;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\Metadata\TraitMethodAlias;
use Typhoon\Reflection\Metadata\TypeMetadata;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-import-type TraitMethodAliases from ClassMetadata
 * @psalm-import-type TraitMethodPrecedence from ClassMetadata
 * @psalm-import-type Visibility from TraitMethodAlias
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
        [$traitMethodAliases, $traitMethodPrecedence] = $this->reflectTraitInfo($class);

        return new ClassMetadata(
            name: $class->name,
            modifiers: $class->getModifiers(),
            changeDetector: ChangeDetector::fromReflection($class),
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
            interfaceTypes: array_map(
                static fn(string $name): InheritedName => new InheritedName($name),
                $class->getInterfaceNames(),
            ),
            traitTypes: array_map(
                static fn(string $name): InheritedName => new InheritedName($name),
                $class->getTraitNames(),
            ),
            traitMethodAliases: $traitMethodAliases,
            traitMethodPrecedence: $traitMethodPrecedence,
            ownProperties: $this->reflectOwnProperties($class),
            ownMethods: $this->reflectOwnMethods($class),
        );
    }

    private function reflectParent(\ReflectionClass $class): ?InheritedName
    {
        $parentClass = $class->getParentClass();

        if ($parentClass === false) {
            return null;
        }

        return new InheritedName($parentClass->name);
    }

    /**
     * @return list<PropertyMetadata>
     */
    private function reflectOwnProperties(\ReflectionClass $class): array
    {
        $properties = [];

        foreach ($class->getProperties() as $property) {
            if ($property->class === $class->name) {
                $name = $property->name;
                \assert($name !== '', 'ReflectionClass always contains default (statically declared) properties with a non-empty name.');

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
            if ($this->isOwnMethod($class, $method)) {
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

    private function isOwnMethod(\ReflectionClass $class, \ReflectionMethod $method): bool
    {
        if ($class->isEnum() && \in_array($method->name, ['cases', 'from', 'tryFrom'], true)) {
            return true;
        }

        if ($method->class !== $class->name) {
            return false;
        }

        return $this->isMethodFromSameFile($class, $method);
    }

    private function isMethodFromSameFile(\ReflectionClass $class, \ReflectionMethod $method): bool
    {
        if ($method->getFileName() !== $class->getFileName()) {
            return false;
        }

        $classStartLine = $class->getStartLine();
        $classEndLine = $class->getEndLine();
        $methodStartLine = $method->getStartLine();
        $methodEndLine = $method->getEndLine();

        return ($methodStartLine === $classStartLine || \is_int($methodStartLine) && \is_int($classStartLine) && $methodStartLine >= $classStartLine)
            && ($methodEndLine === $classEndLine || \is_int($methodEndLine) && \is_int($classEndLine) && $methodEndLine <= $classEndLine);
    }

    /**
     * @return array{TraitMethodAliases, TraitMethodPrecedence}
     */
    private function reflectTraitInfo(\ReflectionClass $class): array
    {
        $traits = $class->getTraits();

        if ($traits === []) {
            return [[], []];
        }

        /** @var TraitMethodAliases */
        $traitMethodAliases = [];
        /** @var TraitMethodPrecedence */
        $traitMethodPrecedence = [];

        foreach ($class->getTraitAliases() as $alias => $traitMethodName) {
            [$traitName, $methodName] = explode('::', $traitMethodName);

            /**
             * @var class-string $traitName
             * @var non-empty-string $methodName
             */
            $traitMethodAliases[$traitName][$methodName][] = new TraitMethodAlias(
                visibility: $this->calculateMethodVisibilityDiff(new \ReflectionMethod($traitName, $methodName), $class->getMethod($alias)),
                alias: $alias,
            );
        }

        foreach ($traits as $trait) {
            foreach ($trait->getMethods() as $traitMethod) {
                $classMethod = $class->getMethod($traitMethod->name);

                if (!$this->isMethodFromSameFile($trait, $classMethod)) {
                    continue;
                }

                $traitMethodPrecedence[$traitMethod->name] = $trait->name;
                $visibilityDiff = $this->calculateMethodVisibilityDiff($traitMethod, $classMethod);

                if ($visibilityDiff !== null) {
                    $traitMethodAliases[$trait->name][$traitMethod->name][] = new TraitMethodAlias($visibilityDiff);
                }
            }
        }

        return [$traitMethodAliases, $traitMethodPrecedence];
    }

    /**
     * @return Visibility
     */
    private function calculateMethodVisibilityDiff(\ReflectionMethod $old, \ReflectionMethod $new): ?int
    {
        if ($new->isPublic()) {
            return $old->isPublic() ? null : \ReflectionMethod::IS_PUBLIC;
        }

        if ($new->isProtected()) {
            return $old->isProtected() ? null : \ReflectionMethod::IS_PROTECTED;
        }

        return $old->isPrivate() ? null : \ReflectionMethod::IS_PRIVATE;
    }

    /**
     * @param ?class-string $class
     * @return list<ParameterMetadata>
     */
    private function reflectParameters(\ReflectionFunctionAbstract $function, ?string $class): array
    {
        $parameters = [];
        $functionOrMethod = $function->name;
        \assert($functionOrMethod !== '');

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
            $name = $attribute->getName();
            \assert(class_exists($name));
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
    private function reflectNativeType(\ReflectionType $reflectionType, ?string $class): Type
    {
        if ($reflectionType instanceof \ReflectionUnionType) {
            return types::union(...array_map(
                fn(\ReflectionType $child): Type => $this->reflectNativeType($child, $class),
                $reflectionType->getTypes(),
            ));
        }

        if ($reflectionType instanceof \ReflectionIntersectionType) {
            return types::intersection(...array_map(
                fn(\ReflectionType $child): Type => $this->reflectNativeType($child, $class),
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

            return types::template('static', types::atClass($class));
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
