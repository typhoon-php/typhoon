--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new StringLiteralType('a'));
/** @psalm-check-type-exact $_type = \'a' */

--EXPECT--
