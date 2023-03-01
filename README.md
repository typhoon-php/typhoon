# PHP Extended Type System Types

Collection of value objects that represent the types of PHP Extended Type System.
Currently, all the types are inspired by popular PHP static analysis tools: [Psalm](https://psalm.dev/) and [PHPStan](https://phpstan.org/).

All implementations of `Type` should be treated as sealed, `Type` interface MUST NOT be implemented in userland!
If you need an alias for a complex compound type, extend `TypeAlias`.

This library will never have any dependencies. Once full and stable, it might be proposed as a [PSR](https://www.php-fig.org/psr/) or [PER](https://www.php-fig.org/per/).

## Installation

```
composer require extended-type-system/type
```

## Naming

Value objects, representing native PHP types cannot be named after them, because words like `Int`, `Strind` etc. are [reserved](https://www.php.net/manual/en/reserved.php).
A suffix might be introduced to fix this problem. `Type` suffix is too verbose, so we have chosen `T`: `int -> IntT`, `float -> FloatT`.
Although types like `non-empty-list` can be safely named `NonEmptyList`, for now we have decided to follow the T-convention for all types.

## Идеи
1. Не запрещать Type
2. Оставить Extended Type System
