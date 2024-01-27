--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new NonEmptyArrayType());
/** @psalm-check-type-exact $_type = \non-empty-array */

--EXPECT--
