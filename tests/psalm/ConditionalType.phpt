--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ConditionalType(
    subject: new Argument('a'),
    if: new LiteralType(true),
    then: StringType::Type,
    else: NullType::Type,
));
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
