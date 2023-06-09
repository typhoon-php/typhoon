<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComposerClassLocator::class)]
final class ComposerClassLocatorTest extends TestCase
{
    public function testItLocatesClass(): void
    {
        $expectedSource = Source::fromFile(__FILE__, 'composer autoloader');
        $locator = new ComposerClassLocator();

        $locatedSource = $locator->locateClass(self::class);

        self::assertEquals($expectedSource, $locatedSource);
    }

    public function testItReturnsNullForInternalClass(): void
    {
        $locator = new ComposerClassLocator();

        $locatedSource = $locator->locateClass(\stdClass::class);

        self::assertNull($locatedSource);
    }
}
