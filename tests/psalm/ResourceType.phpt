--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ResourceType::type);
/** @psalm-check-type-exact $_type = resource */

--EXPECT--
