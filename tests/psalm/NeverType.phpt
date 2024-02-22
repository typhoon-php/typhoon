--FILE--
<?php

namespace Typhoon\Type;

/** @psalm-suppress NoValue */
$_type = PsalmTest::extractType(NeverType::Type);
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
