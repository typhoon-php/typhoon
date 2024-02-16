--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(FloatType::type);
/** @psalm-check-type-exact $_type = float */

--EXPECT--
