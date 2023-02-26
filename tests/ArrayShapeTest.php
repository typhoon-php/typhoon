<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_emptyArrayShape = array */
$_emptyArrayShape = extractType(new ArrayShapeT());

/** @var ArrayShapeT<array{a?: string, 10: int}> */
$_arrayShapeType = new ArrayShapeT([
    'a' => new ArrayShapeItem(new StringT(), optional: true),
    10 => new IntT(),
]);
/** @psalm-check-type-exact $_arrayShape = array{a?: string, 10: int} */
$_arrayShape = extractType($_arrayShapeType);

function testArrayShapeIsCovariant(ArrayShapeT $_type): void
{
}

testArrayShapeIsCovariant($_arrayShapeType);
