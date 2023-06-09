# PHP Extended Type System • Type

Collection of value objects that represent the types of PHP Extended Type System.
All the types are inspired by popular PHP static analysis tools: [Psalm](https://psalm.dev/) and [PHPStan](https://phpstan.org/).

This library will never have any dependencies. Once full and stable, it might be proposed as a [PSR](https://www.php-fig.org/psr/) or [PER](https://www.php-fig.org/per/).

Please, note that this is a low-level API for static analysers and reflectors. It's not designed for convenient general usage in a project.
For that purpose we plan to release a special package. 

## Installation

```
composer require extended-type-system/type
```

## Usage

```php
use ExtendedTypeSystem\types;

/**
 * array{
 *     non-empty-list,
 *     b?: int|float,
 *     Traversable<numeric-string, false>,
 *     d: callable(PDO::*, TSend:Generator=, scalar...): void,
 *     ...
 * }
 */
$type = types::unsealedShape([
    types::nonEmptyString,
    'a' => types::optional(types::union(types::int, types::float)),
    'b' => types::object(Traversable::class, types::numericString, types::false),
    'c' => types::callable(
        parameters: [
            types::classConstant(PDO::class, '*'),
            types::defaultParam(types::classTemplate(Generator::class, 'TSend')),
            types::variadicParam(types::scalar),
        ],
        returnType: types::void,
    ),
]);
```
