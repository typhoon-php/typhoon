# Reflecting types

Typhoon can reflect 4 type kinds (see the [TypeKind](../../src/Reflection/TypeKind.php) enum):
- **Native**
- **Tentative** ([PHP 8.1: Return types in PHP built-in class methods and deprecation notices](https://php.watch/versions/8.1/internal-method-return-types))
- **Annotated** (phpDocs by default)
- **Inferred** from constant value
- **Resolved** that equals `$annotated ?? $inferred ?? $tentative ?? $native ?? types::mixed`

By default `type()` and `returnType()` reflection methods return the `TypeKind::Resolved` type. But you can get
any type by passing the needed kind as a parameter.

Here's an example:

```php
use Typhoon\Reflection\TypeKind;
use Typhoon\Reflection\TyphoonReflector;
use function Typhoon\Type\stringify;

final class A
{
    const int CONSTANT = 1;
    
    /**
     * @var non-empty-string
     */
    public string $property;
}

$class = TyphoonReflector::build()->reflectClass(A::class);

$constant = $class->constants()['CONSTANT'];

var_dump(stringify($constant->type())); // "1"
var_dump($constant->type(TypeKind::Annotated)); // null
var_dump(stringify($constant->type(TypeKind::Inferred))); // "1"
var_dump(stringify($constant->type(TypeKind::Native))); // "int"

$property = $class->properties()['property'];

var_dump(stringify($property->type())); // "non-empty-string"
var_dump(stringify($property->type(TypeKind::Annotated))); // "non-empty-string"
var_dump($property->type(TypeKind::Inferred)); // null
var_dump(stringify($property->type(TypeKind::Native))); // "string"
```
