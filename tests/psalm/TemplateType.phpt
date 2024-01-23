--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new TemplateType('T'));
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
