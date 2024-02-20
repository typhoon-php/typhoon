<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ReflectorMemoryTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testItIsGarbageCollected(): void
    {
        gc_disable();
        $reflector = TyphoonReflector::build();
        $reflection = $reflector->reflectClass(\AppendIterator::class);
        $weakReflector = \WeakReference::create($reflector);
        $weakReflection = \WeakReference::create($reflection);

        unset($reflector, $reflection);

        self::assertNull($weakReflector->get());
        self::assertNull($weakReflection->get());
    }
}
