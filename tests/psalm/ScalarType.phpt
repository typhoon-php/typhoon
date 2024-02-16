--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ScalarType::type);
/** @psalm-check-type-exact $_type = scalar */

--EXPECT--
