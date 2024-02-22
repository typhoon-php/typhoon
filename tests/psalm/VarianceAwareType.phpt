--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new VarianceAwareType(StringType::Type, Variance::Invariant));
/** @psalm-check-type-exact $_type = string */

--EXPECT--
