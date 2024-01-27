--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ConstantType('PHP_MAJOR_VERSION'));
/** @psalm-check-type-exact $_type = \mixed */

--EXPECT--
