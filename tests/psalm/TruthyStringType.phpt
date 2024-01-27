--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(TruthyStringType::type);
/** @psalm-check-type-exact $_type = \truthy-string */

--EXPECT--
