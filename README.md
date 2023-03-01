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

// object(ExtendedTypeSystem\Type\TemplateT) {
//   name => string(1) "T"
//   declaredAt => object(ExtendedTypeSystem\Type\AtClass) {
//     class => string(7) "A"
//   }
// }
var_dump($reflector->reflectPropertyType(A::class, 'a'));

// array(1) {
//   0 => object(ExtendedTypeSystem\Template) {
//     name => string(1) "T"
//     constraint => object(ExtendedTypeSystem\Type\NonEmptyListT) {
//       valueType => object(ExtendedTypeSystem\Type\MixedT) {}
//     }
//     variance => enum(ExtendedTypeSystem\Variance::COVARIANT)
//   }
// }
var_dump($reflector->reflectClassTemplates(A::class));
```
