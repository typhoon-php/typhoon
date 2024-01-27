--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(NonEmptyStringType::type);
/** @psalm-check-type-exact $_type = \non-empty-string */

--EXPECT--
