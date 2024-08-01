# Caching

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
with [FreshCache](../../src/Reflection/Cache/FreshCache.php).

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Reflection\Cache\FreshCache;
use Typhoon\OPcache\TyphoonOPcache;

$reflector = TyphoonReflector::build(
    cache: new FreshCache(new TyphoonOPcache('path/to/cache/dir')),
);
```
