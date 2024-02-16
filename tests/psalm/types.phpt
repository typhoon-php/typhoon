--FILE--
<?php

namespace Typhoon\Type;

$_ints = PsalmTest::extractType(types::list(valueType: types::int));
/** @psalm-check-type-exact $_ints = list<int> */

--EXPECT--
