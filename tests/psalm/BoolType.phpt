--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(BoolType::Type);
/** @psalm-check-type-exact $_type = bool */

--EXPECT--
