<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_array = array */
$_array = extractType(new ArrayT());

/** @psalm-check-type-exact $_intStringArray = array<int, string> */
$_intStringArray = extractType(new ArrayT(new IntT(), new StringT()));

function testArrayIsCovariant(ArrayT $_type): void
{
}

testArrayIsCovariant(new ArrayT(new IntT(), new StringT()));
