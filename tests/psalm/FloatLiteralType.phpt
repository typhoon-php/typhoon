--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new FloatLiteralType(0.5));
/** @psalm-check-type-exact $_type = \0.5 */

--EXPECT--
