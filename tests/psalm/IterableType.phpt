--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new IterableType());
/** @psalm-check-type-exact $_type = iterable */

--EXPECT--
