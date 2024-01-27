--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ClassStringType::type);
/** @psalm-check-type-exact $_type = \class-string */

--EXPECT--
