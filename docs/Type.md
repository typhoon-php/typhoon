# Typhoon Type

Typhoon Type is an abstraction over the PHP static type system, inspired by two popular analyzers [Psalm](https://psalm.dev/) and [PHPStan](https://phpstan.org/).

## Installation

```
composer require typhoon/type
```

## Constructing types

Types should be constructed via the [types](../src/Type/types.php) static factory.

Here's how a monstrous type can be easily expressed in Typhoon:

```
array{
    a: non-empty-list,
    b?: int|float,
    c: Traversable<numeric-string, false>,
    d: callable(PDO::*, TSend:Generator=, scalar...): void,
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
            types::classConstMask(PDO::class),
            types::param(types::classTemplate('TSend', Generator::class), hasDefault: true),
            types::param(types::scalar, variadic: true),
        ],
        return: types::void,
    ),
]);
```

_Note that all classes that implement `Type` (except `types` itself) are `@internal` and should not be accessed directly._

## Analyzing types

Typhoon types should be analyzed only via a [TypeVisitor](../src/Type/TypeVisitor.php): `$type->accept(new MyVisitor())`. Comparison operators and `instanceof`
must not be used with for two reasons:
1. equal types might be represented differently (`array-key = int|string`, `string = ''|non-empty-string`),
2. type classes are internal and not subject to backward compatibility.

### Comparing types

Typhoon team is currently working on a type comparator. Until it is released, you can use [DefaultTypeVisitor](../src/Type/DefaultTypeVisitor.php) for simple checks:

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
