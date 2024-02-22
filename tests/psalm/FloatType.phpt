--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(FloatType::Type);
/** @psalm-check-type-exact $_type = float */

--EXPECT--
