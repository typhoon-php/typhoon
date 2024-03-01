# Typhoon Type

Typhoon Type is an abstraction over the PHP static analysis types. It is the main building block of the whole project.
Typhoon type system is compatible with the popular PHP static analyzers [Psalm](https://psalm.dev/) and [PHPStan](https://phpstan.org/).

This library will never have any dependencies. Once full and stable, it might be proposed as a [PSR](https://www.php-fig.org/psr/) or [PER](https://www.php-fig.org/per/).

## Installation

```
composer require typhoon/type
```

## Constructing types

Types can be constructed via the [types](../src/Type/types.php) static factory.

```php
use Typhoon\Type\types;

/**
 * Equivalent of array{
 *     a: non-empty-list,
 *     b?: int|float,
 *     c: Traversable<numeric-string, false>,
 *     d: callable(PDO::*, TSend:Generator=, scalar...): void,
 *     ...
 * }
 */
$type = types::arrayShape([
    'a' => types::nonEmptyString,
    'b' => types::arrayElement(types::union(types::int, types::float), optional: true),
    'c' => types::object(Traversable::class, types::numericString, types::false),
    'd' => types::callable(
        parameters: [
            types::classConstant(types::object(PDO::class), '*'),
            types::param(types::template('TSend', types::atClass(Generator::class)), hasDefault: true),
            types::param(types::scalar, variadic: true),
        ],
        return: types::void,
    ),
], sealed: false);
```

Note that all classes that implement `Type` (except `types::` itself) are `@internal` and should not be instantiated directly.

## Analyzing types

Types should be analyzed via [TypeVisitor](../src/Type/TypeVisitor.php) or [DefaultTypeVisitor](../src/Type/DefaultTypeVisitor.php). `instanceof`, `==` and `===` operators should not be used for this purpose,
firstly because type classes are internal, secondly because types might be implicitly decorated.

## Checking type relations

Typhoon team is currently working on a type comparator component. For now use [DefaultTypeVisitor](../src/Type/DefaultTypeVisitor.php) to check basic type relations:

```php
use Typhoon\Type\Type;
use Typhoon\Type\DefaultTypeVisitor;

$isMixed = $type->accept(
    new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
        protected function default(Type $self): mixed
        {
            return false;
        }

        public function mixed(Type $self): mixed
        {
            return true;
        }
    },
);
```
