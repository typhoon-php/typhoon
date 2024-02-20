<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NativeReflector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Simple\JustEnum;
use Simple\StringEnum;
use TraitUsage\ClassSimplyUsesTraitAsIs;
use Typhoon\Reflection\FixturesProvider;

/**
 * @psalm-suppress UndefinedClass
 */
#[CoversClass(NativeReflector::class)]
final class NativeReflectorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        FixturesProvider::classes();
    }

    /**
     * @param class-string $class
     * @param non-negative-int $expectedNumberOfOwnMethods
     */
    #[TestWith([ClassSimplyUsesTraitAsIs::class, 0])]
    #[TestWith([JustEnum::class, 1])]
    #[TestWith([StringEnum::class, 3])]
    public function testItDoesNotConsiderTraitMethodsAsOwn(string $class, int $expectedNumberOfOwnMethods): void
    {
        $nativeReflector = new NativeReflector();

        $ownMethods = $nativeReflector->reflectClass(new \ReflectionClass($class))->ownMethods;

        self::assertCount($expectedNumberOfOwnMethods, $ownMethods);
    }
}
