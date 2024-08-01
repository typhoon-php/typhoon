<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @extends DefaultTypeVisitor<NamedTypeAdapter|UnionTypeAdapter|IntersectionTypeAdapter>
 * @todo check array is array<array-key, mixed>?
 */
final class ToNativeTypeConverter extends DefaultTypeVisitor
{
    public function never(Type $type): mixed
    {
        return NamedTypeAdapter::never();
    }

    public function void(Type $type): mixed
    {
        return NamedTypeAdapter::void();
    }

    public function null(Type $type): mixed
    {
        return NamedTypeAdapter::null();
    }

    public function true(Type $type): mixed
    {
        return NamedTypeAdapter::true();
    }

    public function false(Type $type): mixed
    {
        return NamedTypeAdapter::false();
    }

    public function int(Type $type, Type $minType, Type $maxType): mixed
    {
        return NamedTypeAdapter::int();
    }

    public function float(Type $type, Type $minType, Type $maxType): mixed
    {
        return NamedTypeAdapter::float();
    }

    public function string(Type $type): mixed
    {
        return NamedTypeAdapter::string();
    }

    public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed
    {
        return NamedTypeAdapter::array();
    }

    public function iterable(Type $type, Type $keyType, Type $valueType): mixed
    {
        return NamedTypeAdapter::iterable();
    }

    public function object(Type $type, array $properties): mixed
    {
        return NamedTypeAdapter::object();
    }

    public function namedObject(Type $type, NamedClassId $classId, array $typeArguments): mixed
    {
        return NamedTypeAdapter::namedObject($classId->name);
    }

    public function self(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        return NamedTypeAdapter::namedObject('self');
    }

    public function parent(Type $type, array $typeArguments, ?NamedClassId $resolvedClassId): mixed
    {
        return NamedTypeAdapter::namedObject('parent');
    }

    public function static(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        return NamedTypeAdapter::namedObject('static');
    }

    public function callable(Type $type, array $parameters, Type $returnType): mixed
    {
        return NamedTypeAdapter::callable();
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        // TODO use comparator
        if ($type === types::bool) {
            return NamedTypeAdapter::bool();
        }

        $convertedTypes = [];
        $hasNull = false;
        $hasIterable = false;

        foreach ($ofTypes as $ofType) {
            $convertedType = $ofType->accept($this);

            if ($convertedType instanceof UnionTypeAdapter) {
                throw new NonConvertableType($type);
            }

            if ($convertedType instanceof NamedTypeAdapter) {
                if ($convertedType->isNull()) {
                    $hasNull = true;

                    continue;
                }

                if ($convertedType->isIterable()) {
                    $hasIterable = true;

                    continue;
                }
            }

            $convertedTypes[] = $convertedType;
        }

        if ($hasNull) {
            if (\count($convertedTypes) === 0 && $hasIterable) {
                // here we assume that it was ?iterable
                // if it was null|iterable, we should convert to null|array|Traversable,
                // but we have no way to figure that out :(
                return NamedTypeAdapter::iterable()->toNullable();
            }

            if (\count($convertedTypes) === 1 && $convertedTypes[0] instanceof NamedTypeAdapter) {
                return $convertedTypes[0]->toNullable();
            }

            $convertedTypes[] = NamedTypeAdapter::null();
        }

        if ($hasIterable) {
            $convertedTypes[] = NamedTypeAdapter::array();
            $convertedTypes[] = NamedTypeAdapter::namedObject(\Traversable::class);
        }

        \assert(\count($convertedTypes) > 1);

        return new UnionTypeAdapter($convertedTypes);
    }

    public function intersection(Type $type, array $ofTypes): mixed
    {
        return new IntersectionTypeAdapter(array_map(
            function (Type $ofType) use ($type): NamedTypeAdapter {
                $converted = $ofType->accept($this);

                if ($converted instanceof NamedTypeAdapter) {
                    return $converted;
                }

                throw new NonConvertableType($type);
            },
            $ofTypes,
        ));
    }

    public function mixed(Type $type): mixed
    {
        return NamedTypeAdapter::mixed();
    }

    protected function default(Type $type): mixed
    {
        throw new NonConvertableType($type);
    }
}
