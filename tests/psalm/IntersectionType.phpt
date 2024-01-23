--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new IntersectionType([StringType::type, IntType::type]));
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
