<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\NativeReflector\NativeReflector;

#[CoversClass(ClassReflection::class)]
final class ClassReflectionTest extends TestCase
{
    public function testItOverridesAllMethods(): void
    {
        $methods = (new \ReflectionClass(ClassReflection::class))->getMethods();

        foreach ($methods as $method) {
            self::assertSame(
                ClassReflection::class,
                $method->class,
                sprintf('Method %s::%s() should be overridden.', $method->class, $method->name),
            );
        }
    }

    public function testNameProperty(): void
    {
        $metadata = (new NativeReflector())->reflectClass(new \ReflectionClass(\stdClass::class));
        $reflection = new ClassReflection(
            classReflector: $this->createMock(ClassReflector::class),
            metadata: $metadata,
        );

        self::assertTrue(property_exists($reflection, 'name'));
        self::assertSame(\stdClass::class, $reflection->name);
    }
}
