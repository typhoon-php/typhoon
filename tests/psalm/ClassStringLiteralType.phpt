--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ClassStringLiteralType(\stdClass::class));
/** @psalm-check-type-exact $_type = \stdClass::class */

--EXPECT--
