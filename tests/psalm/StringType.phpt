--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(StringType::type);
/** @psalm-check-type-exact $_type = string */

--EXPECT--
