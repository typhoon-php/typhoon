--FILE--
<?php

namespace Typhoon\Type;

$_positiveInt = types::positiveInt;
/** @psalm-check-type-exact $_positiveInt = \Typhoon\Type\IntRangeType */

$_negativeInt = types::negativeInt;
/** @psalm-check-type-exact $_negativeInt = \Typhoon\Type\IntRangeType */

$_nonPositiveInt = types::nonPositiveInt;
/** @psalm-check-type-exact $_nonPositiveInt = \Typhoon\Type\IntRangeType */

$_nonNegativeInt = types::nonNegativeInt;
/** @psalm-check-type-exact $_nonNegativeInt = \Typhoon\Type\IntRangeType */

--EXPECT--
