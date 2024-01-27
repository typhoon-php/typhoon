--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new CallableType());
/** @psalm-check-type-exact $_type = \callable(): mixed */

--EXPECT--
