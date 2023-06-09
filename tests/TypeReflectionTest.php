<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use ExtendedTypeSystem\TypeVisitor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TypeReflection::class)]
final class TypeReflectionTest extends TestCase
{
    public function testItCorrectlyCreatesFromNative(): void
    {
        $reflection = TypeReflection::fromNative(types::string);

        self::assertEquals(
            new TypeReflection(resolved: types::string, native: types::string, phpDoc: null),
            $reflection,
        );
    }
    public function testItCorrectlyCreatesFromPHPDoc(): void
    {
        $reflection = TypeReflection::fromPHPDoc(types::string);

        self::assertEquals(
            new TypeReflection(resolved: types::string, native: null, phpDoc: types::string),
            $reflection,
        );
    }

    public function testItResolvesOnlyResolvedType(): void
    {
        $reflection = new TypeReflection(resolved: types::float, native: types::int, phpDoc: types::string);
        /** @var MockObject&TypeVisitor<Type> */
        $typeResolver = $this->createMock(TypeVisitor::class);
        $typeResolver->method(self::anything())
            ->willReturn(types::null);

        $resolved = $reflection->resolveTypes($typeResolver);

        self::assertEquals(
            new TypeReflection(resolved: types::null, native: types::int, phpDoc: types::string),
            $resolved,
        );
    }

    public function testItKeepsInstanceIfResolvesToSameType(): void
    {
        $reflection = new TypeReflection(resolved: types::float, native: null, phpDoc: null);
        /** @var MockObject&TypeVisitor<Type> */
        $typeResolver = $this->createMock(TypeVisitor::class);
        $typeResolver->method(self::anything())
            ->willReturn(types::float);

        $resolved = $reflection->resolveTypes($typeResolver);

        self::assertSame($reflection, $resolved);
    }
}
