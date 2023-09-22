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
use Typhoon\Type\types;

/**
 * @template T
 */
final readonly class Article
{
    /**
     * @param non-empty-list<non-empty-string> $tags
     * @param T $data
     */
    public function __construct(
        private array $tags,
        public mixed $data,
    ) {}
}

$reflector = TyphoonReflector::build();
$articleReflection = $reflector->reflectClass(Article::class);

var_dump($articleReflection->isFinal()); // true
var_dump($articleReflection->getNamespaceName()); // 'My\Awesome\App'

$tagsReflection = $articleReflection->getProperty('tags');

var_dump($tagsReflection->isReadOnly()); // true
var_dump($tagsReflection->getType()->getResolved()); // object representation of non-empty-list<non-empty-string> type

$dataReflection = $articleReflection->getProperty('data');

var_dump($dataReflection->isPublic()); // true
var_dump($dataReflection->getType()->getResolved()); // object representation of T:Article type

var_dump(
    $articleReflection
        ->withResolvedTemplates(['T' => types::bool])
        ->getProperty('data')
        ->getType()
        ->getResolved(),
); // object representation of bool type
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

## Class loaders

By default, reflector uses `ComposerClassLoader` if it detects composer autoloading, 
`PhpStormStubsClassLoader` if `jetbrains/phpstorm-stubs` is installed 
and `NativeReflectionClassLoader`.
You can implement your own loaders and pass them to `build` method:

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Reflection\ClassLoader;

final class MyClassLoader implements ClassLoader
{
    // ...
}

$reflector = TyphoonReflector::build(
    classLoaders: [
        new MyClassLoader(),
        new ClassLoader\ComposerClassLoader(),
        new ClassLoader\PhpStormStubsClassLoader(),
        new ClassLoader\NativeReflectionClassLoader(),
    ],
);
```

## TODO

- [ ] attributes
- [ ] traits
- [ ] class constants
- [ ] functions
