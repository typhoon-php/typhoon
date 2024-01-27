--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ArrayShapeType());
/** @psalm-check-type-exact $_type = \array */

--EXPECT--
