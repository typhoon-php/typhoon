--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(NumericType::type);
/** @psalm-check-type-exact $_type = \numeric */

--EXPECT--
