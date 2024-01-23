--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(NumericStringType::type);
/** @psalm-check-type-exact $_type = numeric-string */

--EXPECT--
