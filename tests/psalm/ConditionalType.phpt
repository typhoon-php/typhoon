--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ConditionalType(
    subject: new Argument('a'),
    is: StringType::type,
    if: StringType::type,
    else: StringType::type,
));
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
