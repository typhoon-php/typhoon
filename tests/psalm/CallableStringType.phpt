--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(CallableStringType::type);
/** @psalm-check-type-exact $_type = \callable-string */

--EXPECT--
