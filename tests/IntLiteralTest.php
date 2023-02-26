<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_intLiteral = 123 */
$_intLiteral = extractType(new IntLiteralT(123));

/** @psalm-check-type-exact $_negativeIntLiteral = -223 */
$_negativeIntLiteral = extractType(new IntLiteralT(-223));

/** @psalm-check-type-exact $_genericInt = int */
$_genericInt = extractType(new IntLiteralT(crc32('')));

/**
 * @param IntLiteralT<1|2> $_type
 */
function testIntLiteralIsCovariant(IntLiteralT $_type): void
{
}

testIntLiteralIsCovariant(new IntLiteralT(1));
