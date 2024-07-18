# Typhoon Type Stringifier

## Installation

```
composer require typhoon/type-stringifier
```

## Usage

```php
use Typhoon\Type\types;
use function Typhoon\Type\stringify;

echo stringify(
    types::unsealedArrayShape([
        'a' => types::nonEmptyString,
        'b' => types::optional(types::union(types::int, types::float)),
        'c' => types::object(Traversable::class, [types::numericString, types::false]),
        'd' => types::callable(
            parameters: [
                types::classConst(PDO::class, '*'),
                types::param(types::classTemplate('TSend', Generator::class), hasDefault: true),
                types::param(types::scalar, variadic: true),
            ],
            return: types::void,
        ),
    ], sealed: false),
);
```

```
array{a: non-empty-string, b?: int|float, c: Traversable<numeric-string, false>, d: callable(PDO::*, TSend:Generator=, bool|int|float|string...): void, ...}
```
