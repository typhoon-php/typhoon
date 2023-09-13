<?php

declare(strict_types=1);

namespace Typhoon\Type;

/** @psalm-check-type-exact $_emptyShape = array */
$_emptyShape = extractType(new ShapeType());

/** @var ShapeType<array{a?: string, 10: int}> */
$_shapeType = new ShapeType([
    'a' => new ShapeElement(StringType::type, optional: true),
    10 => new ShapeElement(IntType::type),
]);
/** @psalm-check-type-exact $_shape = array{a?: string, 10: int} */
$_shape = extractType($_shapeType);

function testShapeIsCovariant(ShapeType $_type): void
{
}

testShapeIsCovariant($_shapeType);
