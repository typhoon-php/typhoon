--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ArrayType());
/** @psalm-check-type-exact $_type = \array */

new ArrayType(ObjectType::type, ObjectType::type);

--EXPECT--
InvalidArgument on line 9: Argument 1 of Typhoon\Type\ArrayType::__construct expects Typhoon\Type\Type<array-key>, but enum(Typhoon\Type\ObjectType::type) provided
