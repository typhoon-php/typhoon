<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @extends DefaultTypeVisitor<\ReflectionType>
 * @todo check array is array<array-key, mixed>?
 */
final class ToNativeTypeConverter extends DefaultTypeVisitor
{
    public function never(Type $self): mixed
    {
        return NamedTypeAdapter::never();
    }

    public function void(Type $self): mixed
    {
        return NamedTypeAdapter::void();
    }

    public function null(Type $self): mixed
    {
        return NamedTypeAdapter::null();
    }

    public function true(Type $self): mixed
    {
        return NamedTypeAdapter::true();
    }

    public function false(Type $self): mixed
    {
        return NamedTypeAdapter::false();
    }

    public function int(Type $self, ?int $min, ?int $max): mixed
    {
        if ($min === null && $max === null) {
            return NamedTypeAdapter::int();
        }

        throw new NonConvertableType($self);
    }

    public function float(Type $self): mixed
    {
        return NamedTypeAdapter::float();
    }

    public function string(Type $self): mixed
    {
        return NamedTypeAdapter::string();
    }

    public function array(Type $self, Type $key, Type $value, array $elements): mixed
    {
        return NamedTypeAdapter::array();
    }

    public function iterable(Type $self, Type $key, Type $value): mixed
    {
        return NamedTypeAdapter::iterable();
    }

    public function object(Type $self, array $properties): mixed
    {
        return NamedTypeAdapter::object();
    }

    public function namedObject(Type $self, ClassId $class, array $arguments): mixed
    {
        return NamedTypeAdapter::namedObject($class->name);
    }

    public function self(Type $self, ?ClassId $resolvedClass, array $arguments): mixed
    {
        return NamedTypeAdapter::namedObject('self');
    }

    public function parent(Type $self, ?NamedClassId $resolvedClass, array $arguments): mixed
    {
        return NamedTypeAdapter::namedObject('parent');
    }

    public function static(Type $self, ?ClassId $resolvedClass, array $arguments): mixed
    {
        return NamedTypeAdapter::namedObject('static');
    }

    public function callable(Type $self, array $parameters, Type $return): mixed
    {
        return NamedTypeAdapter::callable();
    }

    public function union(Type $self, array $types): mixed
    {
        // TODO use comparator
        if ($self === types::bool) {
            return NamedTypeAdapter::bool();
        }

        $convertedTypes = [];
        $hasNull = false;

        foreach ($types as $type) {
            $convertedType = $type->accept($this);

            if (!$convertedType instanceof \ReflectionNamedType && !$convertedType instanceof \ReflectionIntersectionType) {
                throw new NonConvertableType($self);
            }

            if ($convertedType instanceof \ReflectionNamedType && $convertedType->getName() === 'null') {
                $hasNull = true;

                continue;
            }

            $convertedTypes[] = $convertedType;
        }

        if ($hasNull) {
            if (\count($convertedTypes) === 1 && $convertedTypes[0] instanceof NamedTypeAdapter) {
                return $convertedTypes[0]->toNullable();
            }

            $convertedTypes[] = NamedTypeAdapter::null();
        }

        \assert(\count($convertedTypes) > 1);

        return new UnionTypeAdapter($convertedTypes);
    }

    public function intersection(Type $self, array $types): mixed
    {
        return new IntersectionTypeAdapter(array_map(
            function (Type $type) use ($self): \ReflectionNamedType {
                $converted = $type->accept($this);

                if ($converted instanceof \ReflectionNamedType) {
                    return $converted;
                }

                throw new NonConvertableType($self);
            },
            $types,
        ));
    }

    public function mixed(Type $self): mixed
    {
        return NamedTypeAdapter::mixed();
    }

    protected function default(Type $self): mixed
    {
        throw new NonConvertableType($self);
    }
}
