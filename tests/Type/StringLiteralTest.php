<?php

declare(strict_types=1);

namespace Typhoon\Type;

/** @psalm-check-type-exact $_stringLiteral = 'abc' */
$_stringLiteral = extractType(new StringLiteralType('abc'));

/**
 * @return literal-string
 */
function generateLiteralString(): string
{
    return 'abc';
}

/** @psalm-check-type-exact $_genericLiteralString = literal-string */
$_genericLiteralString = extractType(new StringLiteralType(generateLiteralString()));

/**
 * @param StringLiteralType<'abc'|'xyz'> $_type
 */
function testStringLiteralIsCovariant(StringLiteralType $_type): void {}

testStringLiteralIsCovariant(new StringLiteralType('abc'));
