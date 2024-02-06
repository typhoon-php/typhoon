<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PropertyReflection::class)]
final class PropertyReflectionTest extends TestCase
{
    public function testItOverridesAllMethods(): void
    {
        $methods = (new \ReflectionClass(PropertyReflection::class))->getMethods();

        foreach ($methods as $method) {
            self::assertSame(
                PropertyReflection::class,
                $method->class,
                sprintf('Method %s::%s() should be overridden.', $method->class, $method->name),
            );
        }
    }
}
