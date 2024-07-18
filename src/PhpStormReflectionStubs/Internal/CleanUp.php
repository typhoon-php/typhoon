<?php

declare(strict_types=1);

namespace Typhoon\PhpStormReflectionStubs\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\ConstReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\FunctionReflectionHook;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\PhpStormReflectionStubs
 */
final class CleanUp implements ConstReflectionHook, FunctionReflectionHook, ClassReflectionHook
{
    private const ATTRIBUTE_PREFIX = 'JetBrains\\';

    public function process(ConstId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        if ($id instanceof NamedClassId && $id->name === \Traversable::class) {
            $data = $data->without(Data::UnresolvedInterfaces);
        }

        return $this->cleanUp($data)
            ->withModifiedIfSet(Data::ClassConsts, fn(array $consts): array => array_map($this->cleanUp(...), $consts))
            ->withModifiedIfSet(Data::Properties, fn(array $properties): array => array_map($this->cleanUp(...), $properties))
            ->withModifiedIfSet(Data::Methods, fn(array $methods): array => array_map($this->cleanUp(...), $methods));
    }

    private function cleanUp(TypedMap $data): TypedMap
    {
        return $data
            ->without(Data::StartLine, Data::EndLine, Data::PhpDoc)
            ->withModifiedIfSet(Data::Attributes, static fn(array $attributes): array => array_values(array_filter(
                $attributes,
                static fn(TypedMap $attribute): bool => !str_starts_with($attribute[Data::AttributeClassName], self::ATTRIBUTE_PREFIX),
            )));
    }
}
