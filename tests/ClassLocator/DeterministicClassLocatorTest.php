<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DeterministicClassLocator::class)]
final class DeterministicClassLocatorTest extends TestCase
{
    public function testItReturnsSourceForDefinedClass(): void
    {
        $expectedSource = Source::fromFile(__FILE__);
        $locator = new DeterministicClassLocator($expectedSource, self::class);

        $locatedSource = $locator->locateClass(self::class);

        self::assertSame($expectedSource, $locatedSource);
    }

    public function testItReturnsNullForNonDefinedClass(): void
    {
        $locator = new DeterministicClassLocator(Source::fromFile(__FILE__), self::class);

        $locatedSource = $locator->locateClass(Source::class);

        self::assertNull($locatedSource);
    }
}
