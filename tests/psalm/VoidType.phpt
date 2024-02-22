--FILE--
<?php

namespace Typhoon\Type;

/** @psalm-suppress AssignmentToVoid */
$_type = PsalmTest::extractType(VoidType::Type);
/** @psalm-check-type-exact $_type = null */

--EXPECT--
