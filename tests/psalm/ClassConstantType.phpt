--FILE--
<?php

namespace Typhoon\Type;

$_type = PsalmTest::extractType(new ClassConstantType(\RecursiveIteratorIterator::class, 'LEAVES_ONLY'));
/** @psalm-check-type-exact $_type = mixed */

--EXPECT--
