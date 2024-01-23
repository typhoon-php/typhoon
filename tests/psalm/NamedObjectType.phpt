--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new NamedObjectType(\stdClass::class));
/** @psalm-check-type-exact $_type = stdClass */

--EXPECT--
