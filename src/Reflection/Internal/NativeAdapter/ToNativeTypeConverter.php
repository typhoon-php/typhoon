<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @extends DefaultTypeVisitor<\ReflectionType>
 */
final class ToNativeTypeConverter extends DefaultTypeVisitor
{
    public function string(Type $self): mixed
    {
        return NamedTypeAdapter::string();
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

    public function array(Type $self, Type $key, Type $value, array $elements): mixed
    {
        return NamedTypeAdapter::array();
    }

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

    public function bool(Type $self): mixed
    {
        return NamedTypeAdapter::bool();
    }

    public function namedObject(Type $self, ClassId|AnonymousClassId $class, array $arguments): mixed
    {
        return NamedTypeAdapter::namedObject($class->name);
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return match ($value) {
            true => NamedTypeAdapter::true(),
            false => NamedTypeAdapter::false(),
            default => throw new NonConvertableType($self),
        };
    }

    public function callable(Type $self, array $parameters, Type $return): mixed
    {
        return NamedTypeAdapter::callable();
    }

    public function object(Type $self, array $properties): mixed
    {
        return NamedTypeAdapter::object();
    }

    public function iterable(Type $self, Type $key, Type $value): mixed
    {
        return NamedTypeAdapter::iterable();
    }

    public function closure(Type $self, array $parameters, Type $return): mixed
    {
        return NamedTypeAdapter::namedObject(\Closure::class);
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
            if (\count($convertedTypes) === 1 && $convertedTypes[0] instanceof NamedTypeAdapter) {
                return $convertedTypes[0]->toNullable();
            }

            $convertedTypes[] = NamedTypeAdapter::null();
        }

        \assert(\count($convertedTypes) > 1);

        return new UnionTypeAdapter($convertedTypes);
    }

    public function static(Type $self, ClassId|AnonymousClassId $class, array $arguments): mixed
    {
        return NamedTypeAdapter::namedObject('static');
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
