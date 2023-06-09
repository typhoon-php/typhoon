<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeResolver;

use ExtendedTypeSystem\types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StaticResolver::class)]
final class StaticResolverTest extends TestCase
{
    public function testItResolvesStaticType(): void
    {
        $type = types::list(
            types::static(
                \stdClass::class,
                types::static(\stdClass::class, types::int),
            ),
        );
        $expectedType = types::list(
            types::object(
                \stdClass::class,
                types::object(\stdClass::class, types::int),
            ),
        );
        $resolver = new StaticResolver(\stdClass::class);

        $resolvedType = $type->accept($resolver);

        self::assertEquals($expectedType, $resolvedType);
    }
}
