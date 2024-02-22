--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new AnyLiteralType(StringType::type));
/** @psalm-check-type-exact $_type = string */

--EXPECT--
