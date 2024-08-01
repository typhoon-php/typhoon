# Typhoon Type

Typhoon Type is an object abstraction over the PHP type system, used to express any sophisticated PHP type. It is mostly
inspired by two popular static analyzers: [Psalm](https://psalm.dev/) and [PHPStan](https://phpstan.org/). Typhoon Type
is the main building block for the other Typhoon components.

Unlike other solutions, Typhoon Type does not expose concrete type classes in its API. Instead, it provides only
a [`Type`](../src/Type/Type.php) interface and a [`TypeVisitor`](../src/Type/TypeVisitor.php) with destructurization.
This approach gives several advantages:

1. Memory efficient enums can be used for all atomic types and for aliases of commonly used compound types.
2. The visitor has only a minimal subset of type methods that must be implemented when describing a type algebra.
   Complexity of the other types is hidden and can be completely ignored.
3. Using of downcasting via the `instanceof` operator is automatically discouraged, since all `Type` implementations
   are `@internal`.

## Installation

```
composer require typhoon/type
```

## Constructing types

Typhoon types can be constructed via the `Typhoon\Type\types` static factory. Let's express this monstrous type via
the Typhoon DSL:

```
array{
    a: non-empty-string,
    b?: int|float,
    c: Traversable<numeric-string, false>,
    d: callable(PDO::*, TSend#Generator=, scalar...): void,
    ...
}
```

```php
use Typhoon\Type\types;

$type = types::unsealedArrayShape([
    'a' => types::nonEmptyString,
    'b' => types::optional(types::union(types::int, types::float)),
    'c' => types::object(Traversable::class, [types::numericString, types::false]),
    'd' => types::callable(
        parameters: [
            types::classConstantMask(PDO::class),
            types::param(types::classTemplate(Generator::class, 'TSend'), hasDefault: true),
            types::param(types::scalar, variadic: true),
        ],
        return: types::void,
    ),
]);
```

As you can see, creating types in Typhoon is a lot of fun, especially if you work in IDE with autocompletion 😉

## Printing types

To cast any type to string, use the `Typhoon\Type\stringify()` function:

```php
use Typhoon\Type\types;
use function Typhoon\Type\stringify;

echo stringify(
    types::Generator(
        key: types::nonNegativeInt,
        key: types::classTemplate(Foo::class, 'T'),
        send: types::scalar,
    ),
);

// prints: Generator<int<0, max>, T#Foo, scalar, mixed>
```

### Comparing types

Typhoon team is currently working on a type comparator. Until it is released, you can
use [DefaultTypeVisitor](../src/Type/DefaultTypeVisitor.php) for simple checks:

```php
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

$isIntChecker = new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
    public function int(Type $type, ?int $min, ?int $max): bool
    {
        return true;
    }

    public function intMask(Type $type, Type $ofType): bool
    {
        return true;
    }

    protected function default(Type $type): bool
    {
        return false;
    }
};

var_dump(types::positiveInt->accept($isIntChecker)); // true
var_dump(types::callableString()->accept($isIntChecker)); // false
```

## Compatibility with Psalm and PHPStan

### Native PHP types

| PHPStan                 | Psalm                   | Typhoon                                                                                   |
|-------------------------|-------------------------|-------------------------------------------------------------------------------------------|
| `null`                  | `null`                  | `types::null`                                                                             |
| `void`                  | `void`                  | `types::void`                                                                             |
| `never`                 | `never`                 | `types::never`                                                                            |
| `true`                  | `true`                  | `types::true`, `types::scalar(true)`                                                      |
| `false`                 | `false`                 | `types::false`, `types::scalar(false)`                                                    |
| `bool`, `boolean`       | `bool`                  | `types::bool`                                                                             |
| `int`, `integer`        | `int`                   | `types::int`                                                                              |
| `float`, `double`       | `float`                 | `types::float`                                                                            |
| `string`                | `string`                | `types::string`                                                                           |
| `resource`              | `resource`              | `types::resource`                                                                         |
| `array`                 | `array`                 | `types::array`                                                                            |
| `iterable`              | `iterable`              | `types::iterable`                                                                         |
| `object`                | `object`                | `types::object`                                                                           |
| `Foo`                   | `Foo`                   | `types::object(Foo::class)`                                                               |
| `Closure`               | `Closure`               | `types::Closure` (an alias for `types::object(Closure::class)`)                           |
| `Generator`             | `Generator`             | `types::Generator()` (an alias for `types::object(Generator::class)`)                     |
| `self`                  | `self`                  | `types::self()`                                                                           |
| `parent`                | `parent`                | `types::parent()`                                                                         |
| `static`                | `static`                | `types::static()`                                                                         |
| `callable`              | `callable`              | `types::callable`                                                                         |
| `?string`               | `?string`               | `types::nullable(types::string)`                                                          |
| `int\|string`           | `int\|string`           | `types::union(types::int, types::string)`                                                 |
| `Countable&Traversable` | `Countable&Traversable` | `types::intersection(types::object(Countable::class), types::object(Traversable::class))` |
| `mixed`                 | `mixed`                 | `types::mixed`                                                                            |

### Numbers

| PHPStan                   | Psalm                        | Typhoon                                                         |
|---------------------------|------------------------------|-----------------------------------------------------------------|
| `literal-int`             | `literal-int`                | `types::literalInt`                                             |
| `123`                     | `123`                        | `types::int(123)`, `types::scalar(123)`                         |
| `positive-int`            | `positive-int`               | `types::positiveInt`                                            |
| `negative-int`            | `negative-int`               | `types::negativeInt`                                            |
| `non-positive-int`        | `non-positive-int`           | `types::nonPositiveInt`                                         |
| `non-negative-int`        | `non-negative-int`           | `types::nonNegativeInt`                                         |
| `non-zero-int`            | `negative-int\|positive-int` | `types::nonZeroInt`                                             |
| `int<-5, 6>`              | `int<-5, 6>`                 | `types::intRange(-5, 6)`                                        |
| `int<min, 6>`             | `int<min, 6>`                | `types::intRange(max: 6)`                                       |
| `int<-5, max>`            | `int<-5, max>`               | `types::intRange(min: -5)`                                      |
| `int-mask<1, 2, 4>`       | `int-mask<1, 2, 4>`          | `types::intMask(1, 2, 4)`                                       |
| `int-mask-of<Foo::INT_*>` | `int-mask-of<Foo::INT_*>`    | `types::intMaskOf(types::classConstantMask(Foo::class, 'INT_')` |
| ❌                         | ❌                            | `types::literalFloat`                                           |
| ❌                         | ❌                            | `types::floatRange(-0.001, 2.344)`                              |
| `12.5`                    | `12.5`                       | `types::float(12.5)`, `types::scalar(12.5)`                     |
| `numeric`                 | `numeric`                    | `types::numeric`                                                |

### Strings

| PHPStan                             | Psalm                               | Typhoon                                         |
|-------------------------------------|-------------------------------------|-------------------------------------------------|
| `non-empty-string`                  | `non-empty-string`                  | `types::nonEmptyString`                         |
| `literal-string`                    | `literal-string`                    | `types::literalString`                          |
| `'abc'`                             | `'abc'`                             | `types::string('abc')`, `types::scalar('abc')`  |
| `truthy-string`, `non-falsy-string` | `truthy-string`, `non-falsy-string` | `types::truthyString`, `types::nonFalsyString`  |
| `numeric-string`                    | `numeric-string`                    | `types::numericString`                          |
| `callable-string`                   | `callable-string`                   | `types::callableString()`                       |
| `class-string<Foo>`                 | `class-string<Foo>`                 | `types::classString(types::object(Foo::class))` |
| `Foo::class`                        | `Foo::class`                        | `types::class(Foo::class)`                      |
| `class-string`                      | `class-string`                      | `types::classString`                            |
| ❌                                   | `interface-string`                  | ❌                                               |
| ❌                                   | `trait-string`                      | ❌                                               |
| ❌                                   | `enum-string`                       | ❌                                               |
| ❌                                   | `lowercase-string`                  | ❌                                               |

### Constants

| PHPStan       | Psalm         | Typhoon                                                                                   |
|---------------|---------------|-------------------------------------------------------------------------------------------|
| `PHP_INT_MAX` | `PHP_INT_MAX` | `types::constant('PHP_INT_MAX')`                                                          |
| `Foo::BAR`    | `Foo::BAR`    | `types::classConstant(Foo::class, 'BAR')`                                                 |
| `Foo::IS_*`   | `Foo::IS_*`   | `types::classConstant(Foo::class, 'IS_*')`, `types::classConstantMask(Foo::class, 'IS_')` |

### Arrays and iterables

| PHPStan                                                          | Psalm                                     | Typhoon                                                                                                             |
|------------------------------------------------------------------|-------------------------------------------|---------------------------------------------------------------------------------------------------------------------|
| `array-key`                                                      | `array-key`                               | `types::arrayKey`                                                                                                   |
| `Foo[]`                                                          | `Foo[]`                                   | `types::array(value: types::object(Foo::class))`                                                                    |
| `list<string>`                                                   | `list<string>`                            | `types::list(types::string)`                                                                                        |
| `non-empty-list<string>`                                         | `non-empty-list<string>`                  | `types::nonEmptyList(types::string)`                                                                                |
| `list{int, string}`                                              | `list{int, string}`                       | `types::listShape([types::int, types::string])`                                                                     |
| `list{int, 1?: string}`                                          | `list{int, 1?: string}`                   | `types::listShape([types::int, types::optional(types::string)])`                                                    |
| `list{int, ...}`                                                 | `list{int, ...}`                          | `types::unsealedListShape([types::int])`                                                                            |
| ❌ ([issue](https://github.com/phpstan/phpdoc-parser/issues/245)) | `list{int, ...<string>}`                  | `types::unsealedListShape([types::int], types::string)`                                                             |
| `array<string>`                                                  | `array<string>`                           | `types::array(value: types::string)`                                                                                |
| `array<int, string>`                                             | `array<int, string>`                      | `types::array(types::int, types::string)`                                                                           |
| `non-empty-array<array-key, string>`                             | `non-empty-array<array-key, string>`      | `types::nonEmptyArray(types::arrayKey, types::string)`                                                              |
| `array{}`                                                        | `array{}`                                 | `types::array()`                                                                                                    |
| `array{int, string}`                                             | `array{int, string}`                      | `types::arrayShape([types::int, types::string])`                                                                    |
| `array{int, a?: string}`                                         | `array{int, a?: string}`                  | `types::arrayShape([types::int, 'a' => types::optional(types::string)])`                                            |
| `array{int, ...}`                                                | `array{int, ...}`                         | `types::unsealedArrayShape([types::int])`                                                                           |
| ❌ ([issue](https://github.com/phpstan/phpdoc-parser/issues/245)) | `array{float, ...<int, string>}`          | `types::unsealedArrayShape([types::float], types::int, types::string)`                                              |
| `key-of<Foo::ARRAY>`                                             | `key-of<Foo::ARRAY>`                      | `types::keyOf(types::classConstant(Foo::class, 'ARRAY'))`                                                           |
| `value-of<Foo::ARRAY>`                                           | `value-of<Foo::ARRAY>`                    | `types::valueOf(types::classConstant(Foo::class, 'ARRAY'))`                                                         |
| `TArray[TKey]`                                                   | `TArray[TKey]`                            | `types::offset($arrayType, $keyType)`                                                                               |
| `iterable<object, string>`                                       | `iterable<object, string>`                | `types::iterable(types::object, types::string)`                                                                     |
| `iterable<string>`                                               | `iterable<string>`                        | `types::iterable(value: types::string)`                                                                             |
| `Generator<TKey, TValue, TSend, TReturn>`                        | `Generator<TKey, TValue, TSend, TReturn>` | `types::object(Generator::class, [$key, $value, $send, $return])`, `types::Generator($key, $value, $send, $return)` |
| `callable&array`                                                 | `callable-array`                          | `types::callableArray()`                                                                                            |

### Objects

| PHPStan                 | Psalm                   | Typhoon                                                     |
|-------------------------|-------------------------|-------------------------------------------------------------|
| `Foo<string, float>`    | `Foo<string, float>`    | `types::object(Foo::class, [types::string, types::float])`  |
| `self<string, float>`   | `self<string, float>`   | `types::self([types::string, types::float])`                |
| `parent<string, float>` | `parent<string, float>` | `types::parent([types::string, types::float])`              |
| `static<string, float>` | `static<string, float>` | `types::static([types::string, types::float])`              |
| `object{prop: string}`  | `object{prop: string}`  | `types::object(['prop' => types::string])`                  |
| `object{prop?: string}` | `object{prop?: string}` | `types::object(['prop' => types::optional(types::string)])` |

### Callables

| PHPStan                      | Psalm                        | Typhoon                                                             |
|------------------------------|------------------------------|---------------------------------------------------------------------|
| `callable-string`            | `callable-string`            | `types::callableString()`                                           |
| `callable&array`             | `callable-array`             | `types::callableArray()`                                            |
| `callable(string): void`     | `callable(string): void`     | `types::callable([types::string], types::void)`                     |
| `callable(string=): mixed`   | `callable(string=): mixed`   | `types::callable([types::param(types::string, hasDefault: true)])`  |
| `callable(...string): mixed` | `callable(...string): mixed` | `types::callable([types::param(types::string, variadic: true)])`    |
| `callable(&string): mixed`   | `callable(&string): mixed`   | `types::callable([types::param(types::string, byReference: true)])` |
| `Closure(string): void`      | `Closure(string): void`      | `types::Closure([types::string], types::void)`                      |
| `Closure(string=): mixed`    | `Closure(string=): mixed`    | `types::Closure([types::param(types::string, hasDefault: true)])`   |
| `Closure(...string): mixed`  | `Closure(...string): mixed`  | `types::Closure([types::param(types::string, variadic: true)])`     |
| `Closure(&string): mixed`    | `Closure(&string): mixed`    | `types::Closure([types::param(types::string, byReference: true)])`  |
| `pure-callable`              | `pure-callable`              | ❌                                                                   |

### Other

| PHPStan                             | Psalm                               | Typhoon                                                                                                                         |
|-------------------------------------|-------------------------------------|---------------------------------------------------------------------------------------------------------------------------------|
| `scalar`                            | `scalar`                            | `types::scalar`                                                                                                                 |
| Template `T`                        | Template `T`                        | `types::functionTemplate('foo', 'T')`, `types::classTemplate(Foo::class, 'T')`, `types::methodTemplate(Foo::class, 'bar', 'T')` |
| Alias `X`                           | Alias `X`                           | `types::classAlias(Foo::class, 'X')`                                                                                            |
| `(T is string ? true : false)`      | `(T is string ? true : false)`      | `types::conditional($type, types::string, types::true, types::false)`                                                           |
| `($return is true ? string : void)` | `($return is true ? string : void)` | `types::conditional(types::functionArg('var_export', 'return'), types::true, types::string, types::void)`                       |
| `!null` (only in assertions)        | `!null` (only in assertions)        | `types::not(types::null)`                                                                                                       |
| ❌                                   | `properties-of<T>`                  | ❌                                                                                                                               |
| ❌                                   | `class-string-map<T of Foo, T>`     | ❌                                                                                                                               |
| `open-resource`                     | `open-resource`                     | ❌                                                                                                                               |
| `closed-resource`                   | `closed-resource`                   | ❌                                                                                                                               |
