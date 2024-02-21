<?php

declare(strict_types=1);

namespace Typhoon\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(types::class)]
final class TypesTest extends TestCase
{
    public function testPositiveIntHasCorrectLimits(): void
    {
        $type = types::positiveInt;

        self::assertSame(1, $type->min);
        self::assertNull($type->max);
    }

    public function testNegativeIntHasCorrectLimits(): void
    {
        $type = types::negativeInt;

        self::assertNull($type->min);
        self::assertSame(-1, $type->max);
    }

    public function testNonPositiveIntHasCorrectLimits(): void
    {
        $type = types::nonPositiveInt;

        self::assertNull($type->min);
        self::assertSame(0, $type->max);
    }

    public function testNonNegativeIntHasCorrectLimits(): void
    {
        $type = types::nonNegativeInt;

        self::assertSame(0, $type->min);
        self::assertNull($type->max);
    }

    public function testDefaultArrayReturnsSameInstance(): void
    {
        self::assertSame(types::array(), types::array());
    }

    public function testDefaultIterableReturnsSameInstance(): void
    {
        self::assertSame(types::iterable(), types::iterable());
    }

    public function testIntRangeReturnIntIfNoLimits(): void
    {
        $type = types::intRange();

        self::assertSame(types::int, $type);
    }
}
