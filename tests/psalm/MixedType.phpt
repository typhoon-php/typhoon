--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(MixedType::type);
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
