<?php

declare(strict_types=1);

namespace Typhoon\Type;

/** @psalm-check-type-exact $_floatLiteral = 3.5 */
$_floatLiteral = extractType(new FloatLiteralType(3.5));

/** @psalm-check-type-exact $_negativeFloatLiteral = -1.222 */
$_negativeFloatLiteral = extractType(new FloatLiteralType(-1.222));

/** @psalm-check-type-exact $_genericFloat = float */
$_genericFloat = extractType(new FloatLiteralType(array_sum([1, 2])));

/**
 * @param FloatLiteralType<0.5|-1.7> $_type
 */
function testFloatLiteralIsCovariant(FloatLiteralType $_type): void
{
}

testFloatLiteralIsCovariant(new FloatLiteralType(-1.7));
