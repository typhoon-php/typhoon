--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ValueOfType(new ArrayType()));
/** @psalm-check-type-exact $_type = \mixed */

--EXPECT--
