<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ReflectionHook;

use Typhoon\DeclarationId\ConstId;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ConstReflectionHook
{
    public function process(ConstId $id, TypedMap $data, Reflector $reflector): TypedMap;
}
