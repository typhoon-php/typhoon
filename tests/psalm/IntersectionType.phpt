--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new IntersectionType([StringType::Type, IntType::Type]));
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
