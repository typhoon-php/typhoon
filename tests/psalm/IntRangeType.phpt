--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new IntRangeType());
/** @psalm-check-type-exact $_type = \int */

--EXPECT--
