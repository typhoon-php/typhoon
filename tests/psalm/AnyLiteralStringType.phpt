--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(AnyLiteralStringType::type);
/** @psalm-check-type-exact $_type = literal-string */

--EXPECT--
