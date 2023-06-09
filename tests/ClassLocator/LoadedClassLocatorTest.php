<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

/**
 * @internal
 */
#[CoversClass(LoadedClassLocator::class)]
final class LoadedClassLocatorTest extends TestCase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return array<array{class-string, string}>
     */
    public static function stubs(): array
    {
        return [
            [ClassStub::class, __DIR__ . '/ClassStub.php'],
            [InterfaceStub::class, __DIR__ . '/InterfaceStub.php'],
            [EnumStub::class, __DIR__ . '/EnumStub.php'],
            [TraitStub::class, __DIR__ . '/TraitStub.php'],
        ];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('stubs')]
    public function testItDoesNotLocateNonLoadedClass(string $class): void
    {
        $locator = new LoadedClassLocator();

        $locatedSource = $locator->locateClass($class);

        assertNull($locatedSource);
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('stubs')]
    #[Depends('testItDoesNotLocateNonLoadedClass')]
    public function testItLocatesLoadedClass(string $class, string $expectedFile): void
    {
        $locator = new LoadedClassLocator();
        $expectedSource = Source::fromFile($expectedFile, 'loaded class reflection');
        class_exists($class);

        $locatedSource = $locator->locateClass($class);

        assertEquals($expectedSource, $locatedSource);
    }

    public function testItDoesNotLocateInternalClass(): void
    {
        $locator = new LoadedClassLocator();

        $locatedSource = $locator->locateClass(\stdClass::class);

        self::assertNull($locatedSource);
    }
}
