<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\Reflector\ClassReflector;
use Typhoon\Reflection\Reflector\NativeReflectionReflector;

#[CoversClass(ClassReflectorSetter::class)]
final class ClassReflectorSetterTest extends TestCase
{
    public function testItSetsClassReflector(): void
    {
        $object = new class () {
            public string $prop = 'prop';

            public function method(string $param): void {}
        };
        $reflection = (new NativeReflectionReflector())->reflectClass(
            new \ReflectionClass($object),
            $this->createMock(ClassReflector::class),
        );
        $classReflector = $this->createMock(ClassReflector::class);
        $unserialized = unserialize(serialize($reflection));
        \assert($unserialized instanceof ClassReflection);

        ClassReflectorSetter::set($unserialized, $classReflector);

        $this->assertClassReflectorSame($unserialized, $classReflector);
        foreach ($unserialized->getProperties() as $property) {
            $this->assertClassReflectorSame($property, $classReflector);
        }
        foreach ($unserialized->getMethods() as $method) {
            $this->assertClassReflectorSame($method, $classReflector);
            foreach ($method->getParameters() as $parameter) {
                $this->assertClassReflectorSame($parameter, $classReflector);
            }
        }
    }

    private function assertClassReflectorSame(object $object, ClassReflector $classReflector): void
    {
        self::assertSame($classReflector, (new \ReflectionProperty($object, 'classReflector'))->getValue($object));
    }
}
