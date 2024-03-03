<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeReflection;

use Typhoon\Type\AtClass;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;
use Typhoon\Type\DefaultTypeVisitor;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @extends DefaultTypeVisitor<\ReflectionType>
 */
final class TypeConverter extends DefaultTypeVisitor
{
    public function string(Type $self): mixed
    {
        return NamedTypeReflection::string();
    }

    public function int(Type $self): mixed
    {
        return NamedTypeReflection::int();
    }

    public function float(Type $self): mixed
    {
        return NamedTypeReflection::float();
    }

    public function array(Type $self, Type $key, Type $value, array $elements): mixed
    {
        return NamedTypeReflection::array();
    }

    public function never(Type $self): mixed
    {
        return NamedTypeReflection::never();
    }

    public function void(Type $self): mixed
    {
        return NamedTypeReflection::void();
    }

    public function null(Type $self): mixed
    {
        return NamedTypeReflection::null();
    }

    public function bool(Type $self): mixed
    {
        return NamedTypeReflection::bool();
    }

    public function namedObject(Type $self, string $class, array $arguments): mixed
    {
        return NamedTypeReflection::namedObject($class);
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return match ($value) {
            true => NamedTypeReflection::true(),
            false => NamedTypeReflection::false(),
            default => throw new NonConvertableType($self),
        };
    }

    public function callable(Type $self, array $parameters, Type $return): mixed
    {
        return NamedTypeReflection::callable();
    }

    public function object(Type $self): mixed
    {
        return NamedTypeReflection::object();
    }

    public function iterable(Type $self, Type $key, Type $value): mixed
    {
        return NamedTypeReflection::iterable();
    }

    public function closure(Type $self, array $parameters, Type $return): mixed
    {
        return NamedTypeReflection::namedObject(\Closure::class);
    }

    public function union(Type $self, array $types): mixed
    {
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
            if (\count($convertedTypes) === 1 && $convertedTypes[0] instanceof NamedTypeReflection) {
                return $convertedTypes[0]->toNullable();
            }

            $convertedTypes[] = NamedTypeReflection::null();
        }

        \assert(\count($convertedTypes) > 1);

        return new UnionTypeReflection($convertedTypes);
    }

    public function template(Type $self, string $name, AtClass|AtFunction|AtMethod $declaredAt, array $arguments): mixed
    {
        if ($name === 'self' || $name === 'parent' || $name === 'static') {
            return NamedTypeReflection::namedObject($name);
        }

        throw new NonConvertableType($self);
    }

    public function intersection(Type $self, array $types): mixed
    {
        return new IntersectionTypeReflection(array_map(
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
        return NamedTypeReflection::mixed();
    }

    protected function default(Type $self): mixed
    {
        throw new NonConvertableType($self);
    }
}
