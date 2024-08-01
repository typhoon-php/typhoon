# Typhoon Reflection

Typhoon Reflection is an alternative to [native PHP Reflection](https://www.php.net/manual/en/book.reflection.php). It
is:

- static (does not run or autoload reflected code),
- fast (due to lazy loading and caching),
- [fully compatible with native reflection](reflection/native_adapters.md),
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

## Documentation

- [Caching](reflection/caching.md)
- [Native reflection adapters](reflection/native_adapters.md)
- [Implementing custom types](reflection/implementing_custom_types.md)
