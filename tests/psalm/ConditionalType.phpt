--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ConditionalType(
    subject: new Argument('a'),
    if: TrueType::type,
    then: StringType::type,
    else: NullType::type,
));
/** @psalm-check-type-exact $_type = \mixed */

--EXPECT--
