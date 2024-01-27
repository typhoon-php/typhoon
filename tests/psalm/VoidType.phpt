--FILE--
<?php

namespace Typhoon\Type;

/** @psalm-suppress AssignmentToVoid */
$_type = PsalmTest::extractType(VoidType::type);
/** @psalm-check-type-exact $_type = \null */

--EXPECT--
