# PHP Extended Type System • Type Stringifier

## Installation

```
composer require extended-type-system/type-stringifier
```

## Usage

```php
use PHP\ExtendedTypeSystem\Type\ArrayShapeItem;
use PHP\ExtendedTypeSystem\Type\ArrayShapeT;
use PHP\ExtendedTypeSystem\Type\IntRangeT;
use PHP\ExtendedTypeSystem\Type\NamedObjectT;
use PHP\ExtendedTypeSystem\Type\StringT;
use PHP\ExtendedTypeSystem\TypeStringifier\TypeStringifier;

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
