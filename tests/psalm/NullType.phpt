--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(NullType::Type);
/** @psalm-check-type-exact $_type = null */

--EXPECT--
