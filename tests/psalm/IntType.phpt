--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(IntType::type);
/** @psalm-check-type-exact $_type = int */

--EXPECT--
