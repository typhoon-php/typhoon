--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(EnumStringType::type);
/** @psalm-check-type-exact $_type = \enum-string */

--EXPECT--
