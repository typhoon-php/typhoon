<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MethodReflection::class)]
final class MethodReflectionTest extends TestCase
{
    public function testItOverridesAllMethods(): void
    {
        $methods = (new \ReflectionClass(MethodReflection::class))->getMethods();

        foreach ($methods as $method) {
            self::assertSame(
                MethodReflection::class,
                $method->class,
                sprintf('Method %s::%s() should be overridden.', $method->class, $method->name),
            );
        }
    }
}
