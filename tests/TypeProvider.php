<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Stub\Main;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class TypeProvider
{
    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct()
    {
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public static function nativeTypes(): \Generator
    {
        yield 'no type' => ['', types::mixed];
        yield 'bool' => ['bool', types::bool];
        yield 'int' => ['int', types::int];
        yield 'float' => ['float', types::float];
        yield 'string' => ['string', types::string];
        yield 'array' => ['array', types::array()];
        yield 'iterable' => ['iterable', types::iterable()];
        yield 'object' => ['object', types::object];
        yield 'mixed' => ['mixed', types::mixed];
        yield 'Closure' => ['\Closure', types::object(\Closure::class)];
        yield 'string|int|null' => ['string|int|null', types::union(types::string, types::int, types::null)];
        yield 'string|false' => ['string|false', types::union(types::string, types::false)];
        yield 'Countable&Traversable' => ['\Countable&\Traversable', types::intersection(types::object(\Countable::class), types::object(\Traversable::class))];
        yield '?int' => ['?int', types::nullable(types::int)];
        yield 'self' => ['self', types::object(Main::class)];
        yield 'ArrayObject parent' => ['parent', types::object(\ArrayObject::class)];

        if (\PHP_VERSION_ID >= 80200) {
            yield 'null' => ['null', types::null];
            yield 'true' => ['true', types::true];
            yield 'false' => ['false', types::false];
            yield '(Countable&Traversable)|string' => ['(\Countable&\Traversable)|string', types::union(
                types::intersection(types::object(\Countable::class), types::object(\Traversable::class)),
                types::string,
            )];
        }
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public static function callableType(): \Generator
    {
        yield 'callable' => ['callable', types::callable()];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public static function voidType(): \Generator
    {
        yield 'void' => ['void', types::void];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public static function neverType(): \Generator
    {
        yield 'never' => ['never', types::never];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public static function staticType(): \Generator
    {
        yield 'static' => ['static', types::static(Main::class)];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public static function all(): \Generator
    {
        yield from self::nativeTypes();
        yield from self::callableType();
        yield from self::voidType();
        yield from self::neverType();
        yield from self::staticType();

        yield 'array-key' => ['array-key', types::arrayKey];
    }
}
