<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributeReflection::class)]
final class AttributeReflectionTest extends TestCase
{
    public function testItOverridesAllMethods(): void
    {
        $methods = (new \ReflectionClass(AttributeReflection::class))->getMethods();

        foreach ($methods as $method) {
            self::assertSame(
                AttributeReflection::class,
                $method->class,
                sprintf('Method %s::%s() should be overridden.', $method->class, $method->name),
            );
        }
    }
}
