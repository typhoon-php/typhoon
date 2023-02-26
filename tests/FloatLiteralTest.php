<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_floatLiteral = 3.5 */
$_floatLiteral = extractType(new FloatLiteralT(3.5));

/** @psalm-check-type-exact $_negativeFloatLiteral = -1.222 */
$_negativeFloatLiteral = extractType(new FloatLiteralT(-1.222));

/** @psalm-check-type-exact $_genericFloat = float */
$_genericFloat = extractType(new FloatLiteralT(array_sum([1, 2])));

/**
 * @param FloatLiteralT<0.5|-1.7> $_type
 */
function testFloatLiteralIsCovariant(FloatLiteralT $_type): void
{
}

testFloatLiteralIsCovariant(new FloatLiteralT(-1.7));
