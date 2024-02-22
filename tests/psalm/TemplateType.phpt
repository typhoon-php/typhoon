--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new TemplateType('T', new AtFunction('trim'), ObjectType::Type));
/** @psalm-check-type-exact $_type = object */

--EXPECT--
