# Typhoon Types Interoperability

## Native PHP types

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
| `Closure`               | `Closure`               | `types::closure` (an alias for `types::object(Closure::class)`)                           |
| `Generator`             | `Generator`             | `types::generator()` (an alias for `types::object(Generator::class)`)                     |
| `self`                  | `self`                  | `types::self()`                                                                           |
| `parent`                | `parent`                | `types::parent()`                                                                         |
| `static`                | `static`                | `types::static()`                                                                         |
| `callable`              | `callable`              | `types::callable`                                                                         |
| `?string`               | `?string`               | `types::nullable(types::string)`                                                          |
| `int\|string`           | `int\|string`           | `types::union(types::int, types::string)`                                                 |
| `Countable&Traversable` | `Countable&Traversable` | `types::intersection(types::object(Countable::class), types::object(Traversable::class))` |
| `mixed`                 | `mixed`                 | `types::mixed`                                                                            |

## Numbers

| PHPStan                   | Psalm                        | Typhoon                                                      |
|---------------------------|------------------------------|--------------------------------------------------------------|
| `literal-int`             | `literal-int`                | `types::literalInt`                                          |
| `123`                     | `123`                        | `types::int(123)`, `types::scalar(123)`                      |
| `positive-int`            | `positive-int`               | `types::positiveInt`                                         |
| `negative-int`            | `negative-int`               | `types::negativeInt`                                         |
| `non-positive-int`        | `non-positive-int`           | `types::nonPositiveInt`                                      |
| `non-negative-int`        | `non-negative-int`           | `types::nonNegativeInt`                                      |
| `non-zero-int`            | `negative-int\|positive-int` | `types::nonZeroInt`                                          |
| `int<-5, 6>`              | `int<-5, 6>`                 | `types::intRange(-5, 6)`                                     |
| `int<min, 6>`             | `int<min, 6>`                | `types::intRange(max: 6)`                                    |
| `int<-5, max>`            | `int<-5, max>`               | `types::intRange(min: -5)`                                   |
| `int-mask<1, 2, 4>`       | `int-mask<1, 2, 4>`          | `types::intMask(1, 2, 4)`                                    |
| `int-mask-of<Foo::INT_*>` | `int-mask-of<Foo::INT_*>`    | `types::intMaskOf(types::classConstMask(Foo::class, 'INT_')` |
| ❌                         | ❌                            | `types::literalFloat`                                        |
| `12.5`                    | `12.5`                       | `types::int(12.5)`, `types::scalar(12.5)`                    |
| `numeric`                 | `numeric`                    | `types::numeric`                                             |

## Strings

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

## Constants

| PHPStan       | Psalm         | Typhoon                                                                             |
|---------------|---------------|-------------------------------------------------------------------------------------|
| `PHP_INT_MAX` | `PHP_INT_MAX` | `types::const('PHP_INT_MAX')`                                                       |
| `Foo::BAR`    | `Foo::BAR`    | `types::classConst(Foo::class, 'BAR')`                                              |
| `Foo::IS_*`   | `Foo::IS_*`   | `types::classConst(Foo::class, 'IS_*')`, `types::classConstMask(Foo::class, 'IS_')` |

## Arrays and iterables

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
| `key-of<Foo::ARRAY>`                                             | `key-of<Foo::ARRAY>`                      | `types::keyOf(types::classConst(Foo::class, 'ARRAY'))`                                                              |
| `value-of<Foo::ARRAY>`                                           | `value-of<Foo::ARRAY>`                    | `types::valueOf(types::classConst(Foo::class, 'ARRAY'))`                                                            |
| `TArray[TKey]`                                                   | `TArray[TKey]`                            | `types::offset($arrayType, $keyType)`                                                                               |
| `iterable<object, string>`                                       | `iterable<object, string>`                | `types::iterable(types::object, types::string)`                                                                     |
| `iterable<string>`                                               | `iterable<string>`                        | `types::iterable(value: types::string)`                                                                             |
| `Generator<TKey, TValue, TSend, TReturn>`                        | `Generator<TKey, TValue, TSend, TReturn>` | `types::object(Generator::class, [$key, $value, $send, $return])`, `types::generator($key, $value, $send, $return)` |
| `callable&array`                                                 | `callable-array`                          | `types::callableArray()`                                                                                            |

## Objects

| PHPStan                 | Psalm                   | Typhoon                                                     |
|-------------------------|-------------------------|-------------------------------------------------------------|
| `Foo<string, float>`    | `Foo<string, float>`    | `types::object(Foo::class, [types::string, types::float])`  |
| `self<string, float>`   | `self<string, float>`   | `types::self([types::string, types::float])`                |
| `parent<string, float>` | `parent<string, float>` | `types::parent([types::string, types::float])`              |
| `static<string, float>` | `static<string, float>` | `types::static([types::string, types::float])`              |
| `object{prop: string}`  | `object{prop: string}`  | `types::object(['prop' => types::string])`                  |
| `object{prop?: string}` | `object{prop?: string}` | `types::object(['prop' => types::optional(types::string)])` |

## Callables

| PHPStan                      | Psalm                        | Typhoon                                                             |
|------------------------------|------------------------------|---------------------------------------------------------------------|
| `callable-string`            | `callable-string`            | `types::callableString()`                                           |
| `callable&array`             | `callable-array`             | `types::callableArray()`                                            |
| `callable(string): void`     | `callable(string): void`     | `types::callable([types::string], types::void)`                     |
| `callable(string=): mixed`   | `callable(string=): mixed`   | `types::callable([types::param(types::string, hasDefault: true)])`  |
| `callable(...string): mixed` | `callable(...string): mixed` | `types::callable([types::param(types::string, variadic: true)])`    |
| `callable(&string): mixed`   | `callable(&string): mixed`   | `types::callable([types::param(types::string, byReference: true)])` |
| `Closure(string): void`      | `Closure(string): void`      | `types::closure([types::string], types::void)`                      |
| `Closure(string=): mixed`    | `Closure(string=): mixed`    | `types::closure([types::param(types::string, hasDefault: true)])`   |
| `Closure(...string): mixed`  | `Closure(...string): mixed`  | `types::closure([types::param(types::string, variadic: true)])`     |
| `Closure(&string): mixed`    | `Closure(&string): mixed`    | `types::closure([types::param(types::string, byReference: true)])`  |
| `pure-callable`              | `pure-callable`              | ❌                                                                   |

## Other

| PHPStan                           | Psalm                             | Typhoon                                                                                                                         |
|-----------------------------------|-----------------------------------|---------------------------------------------------------------------------------------------------------------------------------|
| `scalar`                          | `scalar`                          | `types::scalar`                                                                                                                 |
| Template `T`                      | Template `T`                      | `types::functionTemplate('foo', 'T')`, `types::classTemplate(Foo::class, 'T')`, `types::methodTemplate(Foo::class, 'bar', 'T')` |
| Alias `X`                         | Alias `X`                         | `types::classAlias(Foo::class, 'X')`                                                                                            |
| `(T is string ? true : false)`    | `(T is string ? true : false)`    | `types::conditional($type, types::string, types::true, types::false)`                                                           |
| `($arg is string ? true : false)` | `($arg is string ? true : false)` | `types::conditional(types::arg('arg'), types::string, types::true, types::false)`                                               |
| `!null` (only in assertions)      | `!null` (only in assertions)      | `types::not(types::null)`                                                                                                       |
| ❌                                 | `properties-of<T>`                | ❌                                                                                                                               |
| ❌                                 | `class-string-map<T of Foo, T>`   | ❌                                                                                                                               |
| `open-resource`                   | `open-resource`                   | ❌                                                                                                                               |
| `closed-resource`                 | `closed-resource`                 | ❌                                                                                                                               |
