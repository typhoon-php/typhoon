<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\TypeReflection;
use ExtendedTypeSystem\Reflection\TypeReflector;
use ExtendedTypeSystem\types;
use N;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class ItReflectsPHPDocTypeAtPropertyTest extends TypeReflectorTestCase
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
                class X { /** @var */ public $p; }
                PHP,
            new TypeReflection(types::mixed, null, null),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var bool */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::bool),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var int */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::int),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var float */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::float),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var string */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::string),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var array */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::array()),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var iterable */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::iterable()),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var object */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::object),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var mixed */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::mixed),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var \Closure */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::object(\Closure::class)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var string|int|null */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::union(types::string, types::int, types::null)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var string|false */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::union(types::string, types::false)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var \Countable&\Traversable */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::intersection(
                types::object(\Countable::class),
                types::object(\Traversable::class),
            )),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var ?int */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::nullable(types::int)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var self */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::object(N\X::class)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var static */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::static(N\X::class)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class A {}
                class X extends A { /** @var parent */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::object(N\A::class)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class A {}
                class B extends A { /** @var parent */ public $p; }
                class X extends B {}
                PHP,
            TypeReflection::fromPHPDoc(types::object(N\A::class)),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var null */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::null),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var true */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::true),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var false */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::false),
        ];
        yield [
            <<<'PHP'
                namespace N;
                class X { /** @var (\Countable&\Traversable)|string */ public $p; }
                PHP,
            TypeReflection::fromPHPDoc(types::union(
                types::intersection(types::object(\Countable::class), types::object(\Traversable::class)),
                types::string,
            )),
        ];
    }
}
