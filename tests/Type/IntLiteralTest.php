<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_intLiteral = 123 */
$_intLiteral = extractType(new IntLiteralT(123));

/** @psalm-check-type-exact $_negativeIntLiteral = -223 */
$_negativeIntLiteral = extractType(new IntLiteralT(-223));

/**
 * @return literal-int
 */
function generateLiteralInt(): int
{
    return 123;
}

/** @psalm-check-type-exact $_genericLiteralInt = literal-int */
$_genericLiteralInt = extractType(new IntLiteralT(generateLiteralInt()));

/**
 * @param IntLiteralT<1|2> $_type
 */
function testIntLiteralIsCovariant(IntLiteralT $_type): void
{
}

testIntLiteralIsCovariant(new IntLiteralT(1));
