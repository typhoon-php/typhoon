--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(ResourceType::Type);
/** @psalm-check-type-exact $_type = resource */

--EXPECT--
