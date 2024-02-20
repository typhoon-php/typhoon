# Typhoon Reflection

This library is an alternative to [native PHP Reflection](https://www.php.net/manual/en/book.reflection.php). It is:
- static,
- lazy (does not load inherited classes until you reflect properties or methods),
- [PSR-16](https://www.php-fig.org/psr/psr-16/) cacheable,
- [99% compatible with native reflection](docs/compatibility.md),
- supports most of the Psalm/PHPStan types,
- can resolve templates,
- does not create circular object references (can be safely used with [zend.enable_gc=0](https://www.php.net/manual/en/info.configuration.php#ini.zend.enable-gc)).

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

$tagsReflection = $articleReflection->getProperty('tags');

var_dump($tagsReflection->getTyphoonType()); // object representation of non-empty-list<non-empty-string> type

$dataReflection = $articleReflection->getProperty('data');

var_dump($dataReflection->getTyphoonType()); // object representation of T template type
```

## Caching

By default, Typhoon Reflection uses in-memory LRU cache which should be enough for the majority of use cases.

However, if you need persistent cache, you can use any [PSR-16](https://www.php-fig.org/psr/psr-16/) implementation. We highly recommend [Typhoon OPcache](https://github.com/typhoon-php/opcache).
It stores values as php files that could be opcached. It is much faster than an average file cache implementation that uses `serialize`. 

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\OPcache\TyphoonOPcache;

$reflector = TyphoonReflector::build(
    cache: new TyphoonOPcache('path/to/cache/dir'),
);
```

To detect file changes during development, decorate your cache with [FreshCache](src/Cache/FreshCache.php).

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Reflection\Cache\FreshCache;
use Typhoon\OPcache\TyphoonOPcache;

$reflector = TyphoonReflector::build(
    cache: new FreshCache(new TyphoonOPcache('path/to/cache/dir')),
);
```

## Class locators

By default, reflector uses:
- [ComposerClassLocator](src/ClassLocator/ComposerClassLocator.php) if composer autoloading is used, 
- [PhpStormStubsClassLocator](src/ClassLocator/PhpStormStubsClassLocator.php) if `jetbrains/phpstorm-stubs` is installed,
- [NativeReflectionFileLocator](src/ClassLocator/NativeReflectionFileLocator.php) (tries to detect class file via native reflection),
- [NativeReflectionLocator](src/ClassLocator/NativeReflectionLocator.php) (returns native reflection).

You can implement your own locators and pass them to the `build` method:

```php
use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\TyphoonReflector;

final class MyClassLocator implements ClassLocator
{
    // ...
}

$reflector = TyphoonReflector::build(
    classLocators: [
        new MyClassLocator(),
        ...TyphoonReflector::defaultClassLocators(),
    ],
);
```

## TODO

- [ ] traits
- [ ] class constants
- [ ] functions
