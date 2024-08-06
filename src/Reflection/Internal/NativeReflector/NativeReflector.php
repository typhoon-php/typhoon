<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeReflector;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\PhpExtensionVersionChangeDetector;
use Typhoon\ChangeDetector\PhpVersionChangeDetector;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Deprecation;
use Typhoon\Reflection\Internal\ConstantExpression\ClassConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\ConstantExpression\Value;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\PassedBy;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\Reflection\Internal\class_like_exists;
use function Typhoon\Reflection\Internal\get_namespace;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NativeReflector
{
    private const CORE_EXTENSION = 'Core';

    public function reflectConstant(ConstantId $id): ?TypedMap
    {
        if (!\defined($id->name)) {
            return null;
        }

        $value = \constant($id->name);
        $extension = $this->constantExtensions()[$id->name] ?? null;

        return (new TypedMap())
            ->with(Data::Type, new TypeData(inferred: types::value(\constant($id->name))))
            ->with(Data::Namespace, get_namespace($id->name))
            ->with(Data::ValueExpression, Value::from($value))
            ->with(Data::PhpExtension, $extension)
            ->with(Data::InternallyDefined, $extension !== null);
    }

    public function reflectNamedFunction(NamedFunctionId $id): ?TypedMap
    {
        if (!\function_exists($id->name)) {
            return null;
        }

        $function = new \ReflectionFunction($id->name);

        if (!$function->isInternal()) {
            return null;
        }

        return $this->reflectFunctionLike($function, static: $function->getClosureCalledClass(), self: $function->getClosureScopeClass())
            ->with(Data::ChangeDetector, $this->reflectChangeDetector($function))
            ->with(Data::InternallyDefined, true)
            ->with(Data::PhpExtension, $function->getExtensionName() === false ? null : $function->getExtensionName())
            ->with(Data::Namespace, $function->getNamespaceName());
    }

    public function reflectNamedClass(NamedClassId $id): ?TypedMap
    {
        if (!class_like_exists($id->name, autoload: false)) {
            return null;
        }

        $class = new \ReflectionClass($id->name);

        if (!$class->isInternal()) {
            return null;
        }

        $data = (new TypedMap())
            ->with(Data::NativeFinal, $class->isFinal())
            ->with(Data::Abstract, (bool) ($class->getModifiers() & \ReflectionClass::IS_EXPLICIT_ABSTRACT))
            ->with(Data::NativeReadonly, $this->reflectClassNativeReadonly($class))
            ->with(Data::ChangeDetector, $this->reflectChangeDetector($class))
            ->with(Data::InternallyDefined, true)
            ->with(Data::PhpExtension, $class->getExtensionName() === false ? null : $class->getExtensionName())
            ->with(Data::ClassKind, match (true) {
                $class->isInterface() => Data\ClassKind::Interface,
                $class->isTrait() => Data\ClassKind::Trait,
                $class->isEnum() => Data\ClassKind::Enum,
                default => Data\ClassKind::Class_,
            })
            ->with(Data::Properties, $this->reflectProperties($class->getProperties(), $class))
            ->with(Data::Constants, $this->reflectConstants($class->getReflectionConstants(), $class))
            ->with(Data::Methods, $this->reflectMethods($class->getMethods(), $class))
            ->with(Data::Attributes, $this->reflectAttributes($class->getAttributes()))
            ->with(Data::Parents, $this->reflectParents($class))
            ->with(Data::Interfaces, array_fill_keys($class->getInterfaceNames(), []))
            ->with(Data::Cloneable, $class->isCloneable());

        if ($class->isEnum()) {
            $data = $data->with(Data::BackingType, $this->reflectBackingType(new \ReflectionEnum($class->name)));
        }

        return $data;
    }

    private function reflectChangeDetector(\ReflectionFunction|\ReflectionClass $reflection): ChangeDetector
    {
        $extension = $reflection->getExtension();

        if ($extension === null) {
            throw new \LogicException(\sprintf(
                'Internal %s %s is expected to have an extension',
                $reflection instanceof \ReflectionFunction ? 'function' : 'class',
                $reflection->name,
            ));
        }

        if ($extension->name === self::CORE_EXTENSION) {
            return new PhpVersionChangeDetector();
        }

        return PhpExtensionVersionChangeDetector::fromReflection($extension);
    }

    /**
     * @psalm-suppress RedundantCondition, UnusedPsalmSuppress
     */
    private function reflectClassNativeReadonly(\ReflectionClass $class): bool
    {
        return method_exists($class, 'isReadonly') && $class->isReadonly();
    }

    /**
     * @return array<class-string, array{}>
     */
    private function reflectParents(\ReflectionClass $class): array
    {
        $parents = [];
        $parentClass = $class->getParentClass();

        while ($parentClass !== false) {
            $parents[$parentClass->name] = [];
            $parentClass = $parentClass->getParentClass();
        }

        return $parents;
    }

    /**
     * @return ?Type<int|string>
     */
    private function reflectBackingType(\ReflectionEnum $enum): ?Type
    {
        $type = $enum->getBackingType();

        if ($type === null) {
            return null;
        }

        \assert($type instanceof \ReflectionNamedType);

        return $type->getName() === 'int' ? types::int : types::string;
    }

    /**
     * @param array<\ReflectionClassConstant> $constants
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectConstants(array $constants, \ReflectionClass $static): array
    {
        $datas = [];

        foreach ($constants as $constant) {
            $data = (new TypedMap())
                ->with(Data::DeclaringClassId, Id::namedClass($constant->class))
                ->with(Data::Attributes, $this->reflectAttributes($constant->getAttributes()))
                ->with(Data::NativeFinal, (bool) $constant->isFinal())
                ->with(Data::Type, new TypeData($this->reflectClassConstantType($constant, static: $static, self: $constant->getDeclaringClass())))
                ->with(Data::Visibility, $this->reflectVisibility($constant))
                ->with(Data::ValueExpression, Value::from($constant->getValue()));

            if ($constant->isEnumCase()) {
                $enum = new \ReflectionEnum($static->name);
                $data = $data->with(Data::EnumCase, true);

                if ($enum->isBacked()) {
                    $case = $enum->getCase($constant->name);
                    \assert($case instanceof \ReflectionEnumBackedCase);
                    $data = $data->with(Data::BackingValueExpression, Value::from($case->getBackingValue()));
                }
            }

            $datas[$constant->name] = $data;
        }

        return $datas;
    }

    private function reflectClassConstantType(\ReflectionClassConstant $constant, \ReflectionClass $static, \ReflectionClass $self): ?Type
    {
        if (method_exists($constant, 'getType')) {
            /** @var ?\ReflectionType */
            $type = $constant->getType();

            return $this->reflectType($type, static: $static, self: $self);
        }

        return null;
    }

    /**
     * @param array<\ReflectionProperty> $properties
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectProperties(array $properties, \ReflectionClass $static): array
    {
        $data = [];

        foreach ($properties as $property) {
            if ($property->name === '' || !$property->isDefault()) {
                continue;
            }

            $data[$property->name] = (new TypedMap())
                ->with(Data::DeclaringClassId, Id::namedClass($property->class))
                ->with(Data::Attributes, $this->reflectAttributes($property->getAttributes()))
                ->with(Data::Static, $property->isStatic())
                ->with(Data::NativeReadonly, $property->isReadonly())
                ->with(Data::Type, new TypeData(native: $this->reflectType($property->getType(), static: $static, self: $property->getDeclaringClass())))
                ->with(Data::Visibility, $this->reflectVisibility($property))
                ->with(Data::DefaultValueExpression, $property->hasDefaultValue() ? Value::from($property->getDefaultValue()) : null);
        }

        return $data;
    }

    /**
     * @param array<\ReflectionMethod> $methods
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectMethods(array $methods, \ReflectionClass $static): array
    {
        $data = [];

        foreach ($methods as $method) {
            $data[$method->name] = $this->reflectFunctionLike($method, static: $static, self: $method->getDeclaringClass())
                ->with(Data::DeclaringClassId, Id::namedClass($method->class))
                ->with(Data::Visibility, $this->reflectVisibility($method))
                ->with(Data::Static, $method->isStatic())
                ->with(Data::NativeFinal, $method->isFinal())
                ->with(Data::Abstract, $method->isAbstract());
        }

        return $data;
    }

    private function reflectVisibility(\ReflectionClassConstant|\ReflectionProperty|\ReflectionMethod $reflection): Visibility
    {
        return match (true) {
            $reflection->isPrivate() => Visibility::Private,
            $reflection->isProtected() => Visibility::Protected,
            default => Visibility::Public,
        };
    }

    private function reflectFunctionLike(\ReflectionFunctionAbstract $function, ?\ReflectionClass $static, ?\ReflectionClass $self): TypedMap
    {
        return (new TypedMap())
            ->with(Data::Deprecation, $function->isDeprecated() ? new Deprecation() : null)
            ->with(Data::Type, new TypeData(
                native: $this->reflectType($function->getReturnType(), static: $static, self: $self),
                tentative: $this->reflectType($function->getTentativeReturnType(), static: $static, self: $self),
            ))
            ->with(Data::ReturnsReference, $function->returnsReference())
            ->with(Data::Generator, $function->isGenerator())
            ->with(Data::Attributes, $this->reflectAttributes($function->getAttributes()))
            ->with(Data::Parameters, $this->reflectParameters($function->getParameters(), static: $static, self: $self));
    }

    /**
     * @param list<\ReflectionParameter> $parameters
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectParameters(array $parameters, ?\ReflectionClass $static, ?\ReflectionClass $self): array
    {
        $data = [];

        foreach ($parameters as $index => $reflection) {
            $data[$reflection->name] = (new TypedMap())
                ->with(Data::Index, $index)
                ->with(Data::Attributes, $this->reflectAttributes($reflection->getAttributes()))
                ->with(Data::Type, new TypeData($this->reflectType($reflection->getType(), static: $static, self: $self)))
                ->with(Data::PassedBy, match (true) {
                    $reflection->canBePassedByValue() && $reflection->isPassedByReference() => PassedBy::ValueOrReference,
                    $reflection->canBePassedByValue() => PassedBy::Value,
                    default => PassedBy::Reference,
                })
                ->with(Data::DefaultValueExpression, $this->reflectParameterDefaultValueExpression($reflection))
                ->with(Data::Optional, $reflection->isOptional())
                ->with(Data::Promoted, $reflection->isPromoted())
                ->with(Data::Variadic, $reflection->isVariadic());
        }

        return $data;
    }

    private function reflectParameterDefaultValueExpression(\ReflectionParameter $reflection): ?Expression
    {
        if (!$reflection->isDefaultValueAvailable()) {
            return null;
        }

        $constant = $reflection->getDefaultValueConstantName();

        if ($constant === null) {
            return Value::from($reflection->getDefaultValue());
        }

        $parts = explode('::', $constant);

        if (\count($parts) === 1) {
            \assert($parts[0] !== '');

            return new ConstantFetch($parts[0]);
        }

        [$class, $name] = $parts;

        return new ClassConstantFetch(Value::from($class), Value::from($name));
    }

    /**
     * @param array<\ReflectionAttribute> $reflectionAttributes
     * @return list<TypedMap>
     */
    private function reflectAttributes(array $reflectionAttributes): array
    {
        $attributes = [];

        foreach ($reflectionAttributes as $attribute) {
            $attributes[] = (new TypedMap())
                ->with(Data::AttributeClassName, $attribute->getName())
                ->with(Data::ArgumentsExpression, Value::from($attribute->getArguments()));
        }

        return $attributes;
    }

    /**
     * @return ($reflectionType is null ? null : Type)
     */
    private function reflectType(?\ReflectionType $reflectionType, ?\ReflectionClass $static, ?\ReflectionClass $self): ?Type
    {
        if ($reflectionType === null) {
            return null;
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            return types::union(...array_map(
                fn(\ReflectionType $child): Type => $this->reflectType($child, $static, $self),
                $reflectionType->getTypes(),
            ));
        }

        if ($reflectionType instanceof \ReflectionIntersectionType) {
            return types::intersection(...array_map(
                fn(\ReflectionType $child): Type => $this->reflectType($child, $static, $self),
                $reflectionType->getTypes(),
            ));
        }

        if (!$reflectionType instanceof \ReflectionNamedType) {
            throw new \LogicException(\sprintf('Unknown reflection type %s', $reflectionType::class));
        }

        $name = $reflectionType->getName();
        $type = $this->reflectNameAsType($name, $static, $self);

        if ($reflectionType->allowsNull() && $name !== 'null' && $name !== 'mixed') {
            return types::nullable($type);
        }

        return $type;
    }

    /**
     * @param non-empty-string $name
     */
    private function reflectNameAsType(string $name, ?\ReflectionClass $static, ?\ReflectionClass $self): Type
    {
        if ($name === 'self') {
            \assert($self !== null);

            return $self->isTrait() ? types::self() : types::self(resolvedClass: $self->name);
        }

        if ($name === 'parent') {
            \assert($self !== null);

            if ($self->isTrait()) {
                return types::parent();
            }

            $parent = $self->getParentClass();
            \assert($parent !== false);

            return types::parent(resolvedClass: $parent->name);
        }

        if ($name === 'static') {
            \assert($static !== null);

            if ($static->isTrait()) {
                return types::static();
            }

            return types::static(resolvedClass: $static->name);
        }

        return match ($name) {
            'never' => types::never,
            'void' => types::void,
            'null' => types::null,
            'true' => types::true,
            'false' => types::false,
            'bool' => types::bool,
            'int' => types::int,
            'float' => types::float,
            'string' => types::string,
            'array' => types::array,
            'object' => types::object,
            'Closure' => types::Closure,
            'callable' => types::callable,
            'iterable' => types::iterable,
            'resource' => types::resource,
            'mixed' => types::mixed,
            default => types::object($name),
        };
    }

    /**
     * @var ?array<non-empty-string, non-empty-string>
     */
    private ?array $constantExtensions = null;

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    private function constantExtensions(): array
    {
        if ($this->constantExtensions !== null) {
            return $this->constantExtensions;
        }

        $this->constantExtensions = [];

        foreach (get_defined_constants(categorize: true) as $category => $constants) {
            if ($category === 'user') {
                continue;
            }

            foreach ($constants as $name => $_value) {
                /**
                 * @var non-empty-string $name
                 * @var non-empty-string $category
                 */
                $this->constantExtensions[$name] = $category;
            }
        }

        return $this->constantExtensions;
    }
}
