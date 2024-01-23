--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(CallableArrayType::type);
/** @psalm-check-type-exact $_type = callable-array */

--EXPECT--
