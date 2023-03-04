<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_nullable = ?int */
$_nullable = extractType(new NullableType(IntType::self));

/** @psalm-check-type-exact $_nullableNullableInt = ?int */
$_nullableNullableInt = extractType(new NullableType(new NullableType(IntType::self)));

/**
 * @param NullableType<int|string> $_type
 */
function testNullableIsCovariant(NullableType $_type): void
{
}

testNullableIsCovariant(new NullableType(IntType::self));
