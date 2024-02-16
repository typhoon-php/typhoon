--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(FalseType::type);
/** @psalm-check-type-exact $_type = false */

--EXPECT--
