--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new NonEmptyType(StringType::Type));
/** @psalm-check-type-exact $_type = string */

--EXPECT--
