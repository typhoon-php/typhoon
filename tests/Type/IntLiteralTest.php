<?php

declare(strict_types=1);

namespace Typhoon\Type;

/** @psalm-check-type-exact $_intLiteral = 123 */
$_intLiteral = extractType(new IntLiteralType(123));

/** @psalm-check-type-exact $_negativeIntLiteral = -223 */
$_negativeIntLiteral = extractType(new IntLiteralType(-223));

/**
 * @return literal-int
 */
function generateLiteralInt(): int
{
    return 123;
}

/** @psalm-check-type-exact $_genericLiteralInt = literal-int */
$_genericLiteralInt = extractType(new IntLiteralType(generateLiteralInt()));

/**
 * @param IntLiteralType<1|2> $_type
 */
function testIntLiteralIsCovariant(IntLiteralType $_type): void {}

testIntLiteralIsCovariant(new IntLiteralType(1));
