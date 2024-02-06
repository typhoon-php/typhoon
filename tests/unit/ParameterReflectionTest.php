<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParameterReflection::class)]
final class ParameterReflectionTest extends TestCase
{
    public function testItOverridesAllMethods(): void
    {
        $methods = (new \ReflectionClass(ParameterReflection::class))->getMethods();

        foreach ($methods as $method) {
            self::assertSame(
                ParameterReflection::class,
                $method->class,
                sprintf('Method %s::%s() should be overridden.', $method->class, $method->name),
            );
        }
    }
}
