# PHP Extended Type System • Type Stringifier

## Installation

```
composer require extended-type-system/type-stringifier
```

## Usage

```php
use ExtendedTypeSystem\Type\ArrayShapeItem;
use ExtendedTypeSystem\Type\ArrayShapeT;
use ExtendedTypeSystem\Type\IntRangeT;
use ExtendedTypeSystem\Type\NamedObjectT;
use ExtendedTypeSystem\Type\StringT;
use ExtendedTypeSystem\TypeStringifier;

$type = new NamedObjectT(
    ArrayObject::class,
    new IntRangeT(max: 10),
    new ArrayShapeT(
        items: ['a' => new ArrayShapeItem(new StringT(), optional: true)],
        sealed: false,
    ),
);

// ArrayObject<int<min, 10>, array{a?: string, ...}>
echo TypeStringifier::stringify($type);
```
