<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_doubleUnion = true|string */
$_doubleUnion = extractType(new UnionT(new TrueT(), new StringT()));

/** @psalm-check-type-exact $_tripleUnion = true|string|int */
$_tripleUnion = extractType(new UnionT(new TrueT(), new StringT(), new IntT()));

/**
 * @param UnionT<int|string|float> $_type
 */
function testUnionIsCovariant(UnionT $_type): void
{
}

testUnionIsCovariant(new UnionT(new IntT(), new StringT()));
