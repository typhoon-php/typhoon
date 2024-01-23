--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(InterfaceStringType::type);
/** @psalm-check-type-exact $_type = interface-string */

--EXPECT--
