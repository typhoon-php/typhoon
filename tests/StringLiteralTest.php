<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_stringLiteral = 'abc' */
$_stringLiteral = extractType(new StringLiteralT('abc'));

/**
 * @return literal-string
 */
function generateLiteralString(): string
{
    return 'abc';
}

/** @psalm-check-type-exact $_genericLiteralString = literal-string */
$_genericLiteralString = extractType(new StringLiteralT(generateLiteralString()));

/**
 * @param StringLiteralT<'abc'|'xyz'> $_type
 */
function testStringLiteralIsCovariant(StringLiteralT $_type): void
{
}

testStringLiteralIsCovariant(new StringLiteralT('abc'));
