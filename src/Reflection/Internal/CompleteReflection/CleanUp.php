<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

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
 * @psalm-internal Typhoon\Reflection
 */
final class CleanUp implements ConstReflectionHook, FunctionReflectionHook, ClassReflectionHook
{
    public function process(ConstId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        return $data
            ->without(
                Data::TypeContext,
                Data::UnresolvedChangeDetectors,
                Data::UnresolvedInterfaces,
                Data::UnresolvedParent,
                Data::UnresolvedTraits,
                Data::UsePhpDocs,
                Data::TraitMethodAliases,
                Data::TraitMethodPrecedence,
            )
            ->withModifiedIfSet(Data::Methods, static fn(array $methods): array => array_map(
                static fn(TypedMap $data): TypedMap => $data->without(Data::TypeContext),
                $methods,
            ));
    }
}
