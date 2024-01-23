--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(TrueType::type);
/** @psalm-check-type-exact $_type = true */

--EXPECT--
