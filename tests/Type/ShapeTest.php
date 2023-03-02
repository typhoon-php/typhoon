<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_emptyShape = array */
$_emptyShape = extractType(new ShapeT());

/** @var ShapeT<array{a?: string, 10: int}> */
$_arrayShapeType = new ShapeT([
    'a' => new ShapeElement(new StringT(), optional: true),
    10 => new IntT(),
]);
/** @psalm-check-type-exact $_arrayShape = array{a?: string, 10: int} */
$_arrayShape = extractType($_arrayShapeType);

function testShapeIsCovariant(ShapeT $_type): void
{
}

testShapeIsCovariant($_arrayShapeType);
