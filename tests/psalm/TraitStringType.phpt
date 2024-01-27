--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(TraitStringType::type);
/** @psalm-check-type-exact $_type = \trait-string */

--EXPECT--
