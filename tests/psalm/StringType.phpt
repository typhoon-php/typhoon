--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(StringType::Type);
/** @psalm-check-type-exact $_type = string */

--EXPECT--
