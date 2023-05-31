<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\TypeReflector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;

/**
 * @internal
 */
abstract class TypeReflectorTestCase extends TestCase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return \Generator<string, array{string, mixed}>
     */
    final public static function provideNamed(): \Generator
    {
        foreach (static::provide() as $name => [$code, $expectedType]) {
            if (\is_int($name)) {
                $name = preg_replace('/\s+/', ' ', $code);
            }

            yield $name => [$code, $expectedType];
        }
    }

    abstract protected static function reflect(TypeReflector $reflector): mixed;

    /**
     * @return \Generator<int|string, array{string, mixed}>
     */
    abstract protected static function provide(): \Generator;

    #[DataProvider('provideNamed')]
    final public function test(string $code, mixed $expected): void
    {
        $typeReflector = new TypeReflector(new StubLocator('<?php ' . $code));

        $actual = static::reflect($typeReflector);

        assertEquals($expected, $actual);
    }
}
