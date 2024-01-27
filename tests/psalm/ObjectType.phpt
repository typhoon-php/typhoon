--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ObjectType::type);
/** @psalm-check-type-exact $_type = \object */

--EXPECT--
