--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(LiteralStringType::type);
/** @psalm-check-type-exact $_type = \literal-string */

--EXPECT--
