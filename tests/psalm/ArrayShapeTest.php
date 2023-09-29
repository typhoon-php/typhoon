<?php

declare(strict_types=1);

namespace Typhoon\Type;

/** @psalm-check-type-exact $_emptyShape = array */
$_emptyShape = extractType(new ArrayShapeType());

/** @var ArrayShapeType<array{a?: string, 10: int}> */
$_shapeType = new ArrayShapeType([
    'a' => new ArrayElement(StringType::type, optional: true),
    10 => new ArrayElement(IntType::type),
]);
/** @psalm-check-type-exact $_shape = array{a?: string, 10: int} */
$_shape = extractType($_shapeType);

function testShapeIsCovariant(ArrayShapeType $_type): void {}

testShapeIsCovariant($_shapeType);
