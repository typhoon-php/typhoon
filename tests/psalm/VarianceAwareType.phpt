--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new VarianceAwareType(StringType::type, Variance::INVARIANT));
/** @psalm-check-type-exact $_type = string */

--EXPECT--
