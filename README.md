# Typhoon Reflection

This library is an alternative to [native PHP Reflection](https://www.php.net/manual/en/book.reflection.php).
It is static, cacheable, supports Psalm/PHPStan types (`non-empty-string`, `list<T>`, `X::CONSTANT`, etc.) and can resolve templates.

## Installation

```
composer require typhoon/reflection jetbrains/phpstorm-stubs
```

Installing `jetbrains/phpstorm-stubs` is highly recommended.
Without stubs native PHP classes are reflected via native reflector that does not support templates. 

## Basic Usage

```php
namespace My\Awesome\App;

use Typhoon\Reflection\TyphoonReflector;

final readonly class Article
{
    /**
     * @param non-empty-list<non-empty-string> $tags
     */
    public function __construct(
        private array $tags,
    ) {}
}

$reflector = TyphoonReflector::build();
$articleReflection = $reflector->reflectClass(Article::class);

var_dump($articleReflection->isFinal()); // true
var_dump($articleReflection->getNamespaceName()); // 'My\Awesome\App'

$tagsReflection = $articleReflection->getProperty('tags');

var_dump($tagsReflection->isReadOnly()); // true

var_dump($tagsReflection->getType()->getNative());
// class Typhoon\Type\ArrayType (2) {
//   $keyType => enum Typhoon\Type\ArrayKeyType;
//   $valueType => enum Typhoon\Type\MixedType;
// }

var_dump($tagsReflection->getType()->getPhpDoc());
// class Typhoon\Type\NonEmptyListType (1) {
//   $valueType => enum Typhoon\Type\NonEmptyStringType;
// }
```

## Compatibility

This library tries to replicate native reflection API as long as it is possible and makes sense.
See [compatibility](docs/compatibility.md) and [ReflectorCompatibilityTest](tests/ReflectorCompatibilityTest.php) for more details.

The main difference is in `getType` method.

## Caching

```php
use Typhoon\Reflection\TyphoonReflector;

$reflector = TyphoonReflector::build(
    // toggle caching (might be useful in tests)
    // cacheDirectory and detectChanges options have no effect when caching is disabled
    cache: false,
    // set custom cache directory
    // defaults to system temp location according to XDG
    cacheDirectory: '/path/to/cache',
    // optimize DX during development or performance in production
    detectChanges: $_ENV['mode'] !== 'prod',
);
```

## Class locators

By default, reflector uses `ComposerClassLocator` if it detects composer autoloading and `PhpStormStubsClassLocator` if `jetbrains/phpstorm-stubs` is installed.
You can implement your own locators and pass them to `build` method:

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Reflection\ClassLocator;

final class CustomClassLocator implements ClassLocator
{
    // ...
}

$reflector = TyphoonReflector::build(
    classLocator: new ClassLocator\ClassLocatorChain([
        new CustomClassLocator(),
        new ClassLocator\PhpStormStubsClassLocator(),
        new ClassLocator\ComposerClassLocator(),
    ]),
);
```

## TODO

- [ ] anonymous classes
- [ ] attributes
- [ ] traits
- [ ] class constants
- [ ] functions
