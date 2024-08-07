<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CompleteReflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Hook\ClassHook;
use Typhoon\Reflection\Internal\Hook\ConstantHook;
use Typhoon\Reflection\Internal\Hook\FunctionHook;
use Typhoon\Reflection\Internal\Hook\HookPriorities;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
enum RemoveNode implements ConstantHook, FunctionHook, ClassHook
{
    case Instance;

    public function priority(): int
    {
        return HookPriorities::CLEAN_UP;
    }

    public function processConstant(ConstantId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return $data->without(Data::Node);
    }

    public function processFunction(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return $data->without(Data::Node);
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return $data->without(Data::Node);
    }
}
