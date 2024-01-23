--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(BoolType::type);
/** @psalm-check-type-exact $_type = bool */

--EXPECT--
