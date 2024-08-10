<?php

declare(strict_types=1);

namespace Typhoon\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;

#[CoversClass(types::class)]
final class TypeRoutingTest extends TestCase
{
    public function testNever(): void
    {
        types::never->accept(new class extends RoutingTester {
            public function never(Type $type): mixed
            {
                assertSame(types::never, $type);

                return null;
            }
        });
    }

    public function testVoid(): void
    {
        types::void->accept(new class extends RoutingTester {
            public function void(Type $type): mixed
            {
                assertSame(types::void, $type);

                return null;
            }
        });
    }

    public function testNull(): void
    {
        types::null->accept(new class extends RoutingTester {
            public function null(Type $type): mixed
            {
                assertSame(types::null, $type);

                return null;
            }
        });
    }

    public function testBool(): void
    {
        types::bool->accept(new class extends RoutingTester {
            public function union(Type $type, array $ofTypes): mixed
            {
                assertSame(types::bool, $type);
                assertSame([types::true, types::false], $ofTypes);

                return null;
            }
        });
    }

    public function testTrue(): void
    {
        types::true->accept(new class extends RoutingTester {
            public function true(Type $type): mixed
            {
                assertSame(types::true, $type);

                return null;
            }
        });
    }

    public function testFalse(): void
    {
        types::false->accept(new class extends RoutingTester {
            public function false(Type $type): mixed
            {
                assertSame(types::false, $type);

                return null;
            }
        });
    }
}
