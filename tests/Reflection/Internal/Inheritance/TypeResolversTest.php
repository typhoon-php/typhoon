<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Visitor\RecursiveTypeReplacer;

#[CoversClass(TypeResolvers::class)]
final class TypeResolversTest extends TestCase
{
    public function testItReturnsTypeAsIsIfNoResolvers(): void
    {
        $typeResolver = new TypeResolvers();

        $resolvedType = types::array->accept($typeResolver);

        self::assertSame(types::array, $resolvedType);
    }

    public function testItSequentiallyAppliesTypeResolvers(): void
    {
        $neverToVoid = new class extends RecursiveTypeReplacer {
            public function never(Type $type): mixed
            {
                return types::void;
            }
        };
        $voidToMixed = new class extends RecursiveTypeReplacer {
            public function void(Type $type): mixed
            {
                return types::mixed;
            }
        };
        $typeResolvers = new TypeResolvers([$neverToVoid, $voidToMixed]);

        $resolvedType = types::never->accept($typeResolvers);

        self::assertSame(types::mixed, $resolvedType);
    }
}
