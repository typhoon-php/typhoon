--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(NumericStringType::Type);
/** @psalm-check-type-exact $_type = numeric-string */

--EXPECT--
