--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ClassStringType::Type);
/** @psalm-check-type-exact $_type = class-string */

--EXPECT--
