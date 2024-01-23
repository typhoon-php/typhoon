--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(LiteralIntType::type);
/** @psalm-check-type-exact $_type = literal-int */

--EXPECT--
