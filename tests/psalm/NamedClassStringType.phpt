--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new NamedClassStringType(new NamedObjectType(\stdClass::class)));
/** @psalm-check-type-exact $_type = class-string<\stdClass> */

--EXPECT--
