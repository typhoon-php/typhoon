--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new UnionType([StringType::type, IntType::type]));
/** @psalm-check-type-exact $_type = string|int */

--EXPECT--
