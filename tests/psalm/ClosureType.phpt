--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ClosureType());
/** @psalm-check-type-exact $_type = \Closure(): mixed */

--EXPECT--
