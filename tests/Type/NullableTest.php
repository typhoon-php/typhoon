<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_nullable = ?int */
$_nullable = extractType(new NullableT(new IntT()));

/** @psalm-check-type-exact $_nullableNullableInt = ?int */
$_nullableNullableInt = extractType(new NullableT(new NullableT(new IntT())));

/** @psalm-check-type-exact $_nullableUnionNullOrInt = ?int */
$_nullableUnionNullOrInt = extractType(new NullableT(new UnionT(new NullT(), new IntT())));

/**
 * @param NullableT<int|string> $_type
 */
function testNullableIsCovariant(NullableT $_type): void
{
}

testNullableIsCovariant(new NullableT(new IntT()));
