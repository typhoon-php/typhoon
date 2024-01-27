--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ListType());
/** @psalm-check-type-exact $_type = \list */

--EXPECT--
