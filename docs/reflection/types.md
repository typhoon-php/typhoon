# Reflecting types

Typhoon can reflect 4 type kinds (see the [TypeKind](../../src/Reflection/TypeKind.php) enum):
- **Native**
- **Tentative** ([PHP 8.1: Return types in PHP built-in class methods and deprecation notices](https://php.watch/versions/8.1/internal-method-return-types))
- **Annotated** (phpDocs by default)
- **Inferred** from constant value

In addition to that **Resolved** type is 

By default `type()` and `returnType()` reflection methods return a so-called resolved type. It returns the first
non-null type in the following order:
- annotated
- inferred type
- native type

```php
use Typhoon\Reflection\TyphoonReflector;
use function Typhoon\Type\stringify;

/** 
 * @property-read non-empty-string $property
 * @method TReturn method<TArg, TReturn>(TArg $arg, string $default = __CLASS__, ...$variadic)
 */
final class A {}

$reflector = TyphoonReflector::build();

$class = $reflector->reflectClass('A');

$property = $class->properties()['property'];

var_dump($property->isAnnotated()); // true
var_dump($property->isNative()); // false
var_dump($property->isReadonly()); // true
var_dump(stringify($property->type())); // non-empty-string

$method = $class->methods()['method'];

var_dump($method->isAnnotated()); // true
var_dump($method->isNative()); // false
var_dump(stringify($method->returnType())); // TReturn#A::method()
var_dump(stringify($method->parameters()['arg']->type())); // TArg#A::method()
var_dump($method->parameters()['default']->defaultValue()); // A
var_dump($method->parameters()['variadic']->isVariadic()); // true
```
