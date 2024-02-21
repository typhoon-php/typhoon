--FILE--
<?php

namespace Typhoon\Type;

$_true = PsalmTest::extractType(new LiteralType(true));
/** @psalm-check-type-exact $_true = true */

$_false = PsalmTest::extractType(new LiteralType(false));
/** @psalm-check-type-exact $_false = false */

$_int = PsalmTest::extractType(new LiteralType(10));
/** @psalm-check-type-exact $_int = 10 */

$_float = PsalmTest::extractType(new LiteralType(0.2));
/** @psalm-check-type-exact $_float = 0.2 */

$_string = PsalmTest::extractType(new LiteralType('test'));
/** @psalm-check-type-exact $_string = 'test' */

--EXPECT--
