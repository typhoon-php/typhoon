<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/**
 * @param UnionType<int|string|float> $_type
 */
function testUnionIsCovariant(UnionType $_type): void
{
}

testUnionIsCovariant(new UnionType([IntType::type, StringType::type]));
