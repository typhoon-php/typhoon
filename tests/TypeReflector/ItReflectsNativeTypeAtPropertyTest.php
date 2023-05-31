<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\TypeReflection;
use ExtendedTypeSystem\Reflection\TypeReflector;
use ExtendedTypeSystem\types;
use N;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
final class ItReflectsNativeTypeAtPropertyTest extends TypeReflectorTestCase
{
    protected static function reflect(TypeReflector $reflector): mixed
    {
        return $reflector->reflectClassLike(N\X::class)->propertyType('p');
    }

    protected static function provide(): \Generator
    {
        yield [
            <<<'PHP'
                namespace N;
                class X { public $p; }
                PHP,
            new TypeReflection(types::mixed, null, null),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public bool $p; }
                PHP,
            TypeReflection::fromNative(types::bool),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public int $p; }
                PHP,
            TypeReflection::fromNative(types::int),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public float $p; }
                PHP,
            TypeReflection::fromNative(types::float),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public string $p; }
                PHP,
            TypeReflection::fromNative(types::string),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public array $p; }
                PHP,
            TypeReflection::fromNative(types::array()),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public iterable $p; }
                PHP,
            TypeReflection::fromNative(types::iterable()),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public object $p; }
                PHP,
            TypeReflection::fromNative(types::object),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public mixed $p; }
                PHP,
            TypeReflection::fromNative(types::mixed),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public \Closure $p; }
                PHP,
            TypeReflection::fromNative(types::object(\Closure::class)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public string|int|null $p; }
                PHP,
            TypeReflection::fromNative(types::union(types::string, types::int, types::null)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public string|false $p; }
                PHP,
            TypeReflection::fromNative(types::union(types::string, types::false)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public \Countable&\Traversable $p; }
                PHP,
            TypeReflection::fromNative(types::intersection(
                types::object(\Countable::class),
                types::object(\Traversable::class),
            )),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public ?int $p; }
                PHP,
            TypeReflection::fromNative(types::nullable(types::int)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { public self $p; }
                PHP,
            TypeReflection::fromNative(types::object(N\X::class)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class A {}
                class X extends A { public parent $p; }
                PHP,
            TypeReflection::fromNative(types::object(N\A::class)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class A {}
                class B extends A { public parent $p; }
                class X extends B {}
                PHP,
            TypeReflection::fromNative(types::object(N\A::class)),
        ];

        if (\PHP_VERSION_ID >= 80200) {
            yield [
                <<<'PHP'
                    namespace N;
                    class X { public null $p; }
                    PHP,
                TypeReflection::fromNative(types::null),
            ];
            yield [
                <<<'PHP'
                    namespace N;
                    class X { public true $p; }
                    PHP,
                TypeReflection::fromNative(types::true),
            ];
            yield [
                <<<'PHP'
                    namespace N;
                    class X { public false $p; }
                    PHP,
                TypeReflection::fromNative(types::false),
            ];
            yield [
                <<<'PHP'
                    namespace N;
                    class X { public (\Countable&\Traversable)|string $p; }
                    PHP,
                TypeReflection::fromNative(types::union(
                    types::intersection(types::object(\Countable::class), types::object(\Traversable::class)),
                    types::string,
                )),
            ];
        }
    }
}
