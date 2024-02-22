--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(IntType::Type);
/** @psalm-check-type-exact $_type = int */

--EXPECT--
