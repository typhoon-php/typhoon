<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_emptyShape = array */
$_emptyShape = extractType(new ShapeType());

/** @var ShapeType<array{a?: string, 10: int}> */
$_shapeType = new ShapeType([
    new ShapeElement(null, true, StringType::type),
    new ShapeElement(10, false, IntType::type),
]);
/** @psalm-check-type-exact $_shape = array{a?: string, 10: int} */
$_shape = extractType($_shapeType);

function testShapeIsCovariant(ShapeType $_type): void
{
}

testShapeIsCovariant($_shapeType);
