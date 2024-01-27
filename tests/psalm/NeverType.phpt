--FILE--
<?php

namespace Typhoon\Type;

/** @psalm-suppress NoValue */
$_type = PsalmTest::extractType(NeverType::type);
/** @psalm-check-type-exact $_type = \mixed */

--EXPECT--
