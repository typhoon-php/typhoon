--FILE--
<?php

namespace Typhoon\Type;

$_nonExistingClassObject = PsalmTest::extractType(new NamedObjectType('SomeClass'));
/** @psalm-check-type-exact $_nonExistingClassObject = mixed */

$_existingClassObject = PsalmTest::extractType(new NamedObjectType(\stdClass::class));
/** @psalm-check-type-exact $_existingClassObject = \stdClass */

--EXPECT--
