--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new IntLiteralType(123));
/** @psalm-check-type-exact $_type = 123 */

--EXPECT--
