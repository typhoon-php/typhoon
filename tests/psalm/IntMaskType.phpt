--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new IntMaskType([1, 2, 4]));
/** @psalm-check-type-exact $_type = int<0, max> */

--EXPECT--
