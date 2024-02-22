--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new UnionType([StringType::Type, IntType::Type]));
/** @psalm-check-type-exact $_type = string|int */

--EXPECT--
