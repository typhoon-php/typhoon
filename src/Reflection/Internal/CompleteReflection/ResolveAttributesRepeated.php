<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\FunctionReflectionHook;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ResolveAttributesRepeated implements FunctionReflectionHook, ClassReflectionHook
{
    public function process(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        $data = $this->resolveAttributesRepeated($data);

        if ($id instanceof NamedFunctionId || $id instanceof AnonymousFunctionId) {
            return $data;
        }

        return $data
            ->withModifiedIfSet(Data::ClassConsts, fn(array $consts): array => array_map(
                $this->resolveAttributesRepeated(...),
                $consts,
            ))
            ->withModifiedIfSet(Data::Properties, fn(array $properties): array => array_map(
                $this->resolveAttributesRepeated(...),
                $properties,
            ))
            ->withModifiedIfSet(Data::Methods, fn(array $methods): array => array_map(
                fn(TypedMap $data): TypedMap => $this
                    ->resolveAttributesRepeated($data)
                    ->withModifiedIfSet(Data::Parameters, fn(array $parameters): array => array_map(
                        $this->resolveAttributesRepeated(...),
                        $parameters,
                    )),
                $methods,
            ));
    }

    private function resolveAttributesRepeated(TypedMap $data): TypedMap
    {
        $attributes = $data[Data::Attributes];

        if ($attributes === []) {
            return $data;
        }

        $repeated = [];

        foreach ($attributes as $attribute) {
            $class = $attribute[Data::AttributeClassName];
            $repeated[$class] = isset($repeated[$class]);
        }

        return $data->with(Data::Attributes, array_map(
            static fn(TypedMap $attribute): TypedMap => $attribute->with(
                Data::AttributeRepeated,
                $repeated[$attribute[Data::AttributeClassName]],
            ),
            $attributes,
        ));
    }
}
