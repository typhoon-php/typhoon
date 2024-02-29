<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\Type\Type;
use function Typhoon\TypeStringifier\stringify;

abstract class RelationTestCase extends TestCase
{
    /**
     * @return \Generator<non-empty-string, array{Type, Type}>
     */
    final public static function xSubtypeOfYNamed(): \Generator
    {
        foreach (static::xSubtypeOfY() as [$x, $y]) {
            yield sprintf('%s subtype of %s', stringify($x), stringify($y)) => [$x, $y];
        }
    }

    /**
     * @return \Generator<non-empty-string, array{Type, Type}>
     */
    final public static function xNotSubtypeOfYNamed(): \Generator
    {
        foreach (static::xNotSubtypeOfY() as [$x, $y]) {
            yield sprintf('%s not subtype of %s', stringify($x), stringify($y)) => [$x, $y];
        }
    }

    /**
     * @return iterable<array{Type, Type}>
     */
    abstract protected static function xSubtypeOfY(): iterable;

    /**
     * @return iterable<array{Type, Type}>
     */
    abstract protected static function xNotSubtypeOfY(): iterable;

    #[DataProvider('xSubtypeOfYNamed')]
    final public function testXSubtypeOfY(Type $x, Type $y): void
    {
        self::assertTrue(isSubtype($x, $y));
    }

    #[DataProvider('xNotSubtypeOfYNamed')]
    final public function testXNotSubtypeOfY(Type $x, Type $y): void
    {
        self::assertFalse(isSubtype($x, $y));
    }
}
