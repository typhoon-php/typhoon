--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ObjectType::Type);
/** @psalm-check-type-exact $_type = object */

--EXPECT--
