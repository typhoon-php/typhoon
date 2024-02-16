--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(AnyLiteralIntType::type);
/** @psalm-check-type-exact $_type = literal-int */

--EXPECT--
