# Typhoon Type

Typhoon Type is an abstraction for the PHP static type system, inspired by two popular analyzers [Psalm](https://psalm.dev/) and [PHPStan](https://phpstan.org/).

Once full and stable, Typhoon Type might be proposed as a [PSR](https://www.php-fig.org/psr/) or [PER](https://www.php-fig.org/per/).

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

Note that all classes that implement `Type` (except `types::` itself) are `@internal` and should not be used outside the library.

## Analyzing types

Typhoon types should be analyzed only via [TypeVisitors](../src/Type/TypeVisitor.php): `$type->accept(new MyVisitor())`. Comparison operators and `instanceof`
should never be used with Typhoon types for two reasons:
1. type classes are internal and not subject to backward compatibility,
2. equal types might have different internal structure (e.g., one is decorated).

### Comparing types

Typhoon team is currently working on a type comparator. Until it is released, you can use [DefaultTypeVisitor](../src/Type/DefaultTypeVisitor.php) for simple checks:

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
