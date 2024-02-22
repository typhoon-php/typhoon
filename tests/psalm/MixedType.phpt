--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(MixedType::Type);
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
