--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new StaticType());
/** @psalm-check-type-exact $_type = object */

--EXPECT--
