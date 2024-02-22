--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new AliasType('SomeClass', 'AliasType'));
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
