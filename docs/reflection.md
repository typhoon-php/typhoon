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
use Typhoon\Type\types;
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
$class = $reflector->reflectClass(Article::class);
$tagsType = $class->properties()['tags']->type();

var_dump(stringify($tagsType)); // "list<TTag#Article>"

$templateResolver = $class->createTemplateResolver([
    types::union(
        types::string('PHP'),
        types::string('Architecture'),
    ),
]);

var_dump(stringify($tagsType->accept($templateResolver))); // "list<'PHP'|'Architecture'>"
```

## Documentation

- [Native reflection adapters](reflection/native_adapters.md)
- [Reflecting Types](reflection/types.md)
- [Reflecting PHPDoc properties and methods](reflection/php_doc_properties_and_methods.md)
- [Implementing custom types](reflection/implementing_custom_types.md)
- [Caching](reflection/caching.md)

Documentation is still far from being complete. Don't hesitate to create issues to clarify how things work.
