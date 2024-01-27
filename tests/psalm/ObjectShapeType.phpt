--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ObjectShapeType());
/** @psalm-check-type-exact $_type = \object */

--EXPECT--
