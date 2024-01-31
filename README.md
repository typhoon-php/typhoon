# Typhoon Type Stringifier

## Installation

```
composer require typhoon/type-stringifier
```

## Usage

```php
use Typhoon\Type\types;
use Typhoon\TypeStringifier\TypeStringifier;

$type = types::arrayShape([
    'a' => types::nonEmptyString,
    'b' => types::arrayElement(types::union(types::int, types::float), optional: true),
    'c' => types::object(Traversable::class, types::numericString, types::false),
    'd' => types::callable(
        parameters: [
            types::classConstant(PDO::class, '*'),
            types::param(types::template('TSend', types::atClass(Generator::class)), hasDefault: true),
            types::param(types::scalar, variadic: true),
        ],
        returnType: types::void,
    ),
], sealed: false);

// string(141) "array{a: non-empty-string, b?: int|float, c: Traversable<numeric-string, false>, d: callable(PDO::*, TSend:Generator=, scalar...): void, ...}"
var_dump(TypeStringifier::stringify($type));
```
