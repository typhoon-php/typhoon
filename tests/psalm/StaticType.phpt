--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new StaticType(\stdClass::class));
/** @psalm-check-type-exact $_type = \stdClass */

--EXPECT--
