# Reflecting PHPDoc properties and methods

PHPDoc properties and methods are reflected as usual. To differentiate them from the native ones, use
`isAnnotated()` and `isNative()` methods.

PHPDoc methods support templates, variadic parameters and default values.

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
var_dump(stringify($property->type())); // "non-empty-string"

$method = $class->methods()['method'];

var_dump($method->isAnnotated()); // true
var_dump($method->isNative()); // false
var_dump(stringify($method->returnType())); // "TReturn#A::method()"
var_dump(stringify($method->parameters()['arg']->type())); // "TArg#A::method()"
var_dump($method->parameters()['default']->evaluateDefault()); // "A"
var_dump($method->parameters()['variadic']->isVariadic()); // true
```
