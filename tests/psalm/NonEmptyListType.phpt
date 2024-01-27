--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new NonEmptyListType());
/** @psalm-check-type-exact $_type = \non-empty-list */

--EXPECT--
