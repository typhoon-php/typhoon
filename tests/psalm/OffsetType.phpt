--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new OffsetType(new ListType(), new LiteralType(0)));
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
