# PHP Extended Type System • Type Reflection

## Installation

```
composer require extended-type-system/type-reflection
```

## Usage

```php
use ExtendedTypeSystem\TypeReflector;

/**
 * @template-covariant T of non-empty-list
 */
final class A
{
    /**
     * @param T $a
     */
    public function __construct(
        public readonly array $a,
    ) {
    }
}

$reflector = new TypeReflector();
$classReflection = $reflector->reflectClass(A::class);

// object(ExtendedTypeSystem\Type\ClassTemplateType) {
//   name => string(1) "T"
//   class => string(1) "A"
// }
var_dump($classReflection->propertyType('a'));

// array(1) {
//   ["T"] => object(ExtendedTypeSystem\TemplateReflection) {
//     index => int(0)
//     name => string(1) "T"
//     constraint => object(ExtendedTypeSystem\Type\NonEmptyListType) {
//       valueType => object(ExtendedTypeSystem\Type\MixedType) {}
//     }
//     variance => enum(ExtendedTypeSystem\Variance::COVARIANT)
//   }
// }
var_dump($classReflection->templates);
```
