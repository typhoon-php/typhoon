--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ClosedResourceType::type);
/** @psalm-check-type-exact $_type = closed-resource */

--EXPECT--
