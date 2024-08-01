# Typhoon Reflection

Typhoon Reflection is an alternative to [native PHP Reflection](https://www.php.net/manual/en/book.reflection.php). It
is:

- static (does not run or autoload reflected code),
- fast (due to lazy loading and caching),
- [fully compatible with native reflection](reflection/native_reflection_compatibility.md),
- supports most of the Psalm and PHPStan phpDoc types,
- can resolve templates,
- does not leak memory and can be safely used
  with [zend.enable_gc=0](https://www.php.net/manual/en/info.configuration.php#ini.zend.enable-gc).

## Installation

```
composer require typhoon/reflection typhoon/phpstorm-reflection-stubs
```

`typhoon/phpstorm-reflection-stubs` is a bridge for `jetbrains/phpstorm-stubs`. Without this package internal classes
and functions are reflected from native reflection without templates.

## Basic Usage

```php
use Typhoon\Reflection\TyphoonReflector;
use function Typhoon\Type\stringify;

/**
 * @template TTag of non-empty-string
 */
final readonly class Article
{
    /**
     * @param list<TTag> $tags
     */
    public function __construct(
        private array $tags,
    ) {}
}

$reflector = TyphoonReflector::build();
$articleTagsType = $reflector->reflectClass(Article::class)->properties()['tags']->type();

var_dump(stringify($articleTagsType)); // list<TTag#Article>
```

## Caching

By default, Typhoon Reflection uses in-memory LRU cache which should be enough for the majority of use cases.

However, if you need persistent cache, you can use any [PSR-16](https://www.php-fig.org/psr/psr-16/) implementation. We
highly recommend [Typhoon OPcache](https://github.com/typhoon-php/opcache). It stores values as opcacheable php files.

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\OPcache\TyphoonOPcache;

$reflector = TyphoonReflector::build(
    cache: new TyphoonOPcache('path/to/cache/dir'),
);
```

To detect file changes during development, decorate your cache
with [FreshCache](../src/Reflection/Cache/FreshCache.php).

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Reflection\Cache\FreshCache;
use Typhoon\OPcache\TyphoonOPcache;

$reflector = TyphoonReflector::build(
    cache: new FreshCache(new TyphoonOPcache('path/to/cache/dir')),
);
```

## Native reflection adapters

All `*Reflection` classes have a `toNativeReflection()` method that can be used to obtain native PHP reflection
adapters. These adapters do not trigger autoloading for most of the operations.
See [this article](reflection/native_reflection_compatibility.md) for details.

```php
use Typhoon\Reflection\TyphoonReflector;

$isInstantiable = TyphoonReflector::build()
    ->reflectClass(MyClass::class)
    ->toNativeReflection()
    ->isInstantiable();
```
