<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_array = array */
$_array = extractType(new ArrayType());

/** @psalm-check-type-exact $_intStringArray = array<int, string> */
$_intStringArray = extractType(new ArrayType(IntType::self, StringType::self));

function testArrayIsCovariant(ArrayType $_type): void
{
}

testArrayIsCovariant(new ArrayType(IntType::self, StringType::self));
