--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ArrayKeyType::type);
/** @psalm-check-type-exact $_type = \array-key */

--EXPECT--
