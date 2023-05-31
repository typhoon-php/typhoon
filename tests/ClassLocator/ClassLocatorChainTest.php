<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ClassLocatorChain::class)]
final class ClassLocatorChainTest extends TestCase
{
    public function testItReturnsNullSourceForEmptyListOfLocators(): void
    {
        $locator = new ClassLocatorChain([]);

        $located = $locator->locateClass(self::class);

        self::assertNull($located);
    }

    public function testItReturnsFirstLocatedSource(): void
    {
        $expectedSource = Source::fromFile(__FILE__);
        $locator = new ClassLocatorChain([
            new DeterministicClassLocator($expectedSource, self::class),
            new DeterministicClassLocator(Source::fromFile(__FILE__), self::class),
        ]);

        $locatedSource = $locator->locateClass(self::class);

        self::assertSame($expectedSource, $locatedSource);
    }
}
