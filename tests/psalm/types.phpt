--FILE--
<?php

namespace Typhoon\Type;

$_ints = PsalmTest::extractType(types::list(valueType: types::int));
/** @psalm-check-type-exact $_ints = list<int> */

$_nonExistingClassObject = PsalmTest::extractType(types::object('SomeClass'));
/** @psalm-check-type-exact $_nonExistingClassObject = object */

$_existingClassObject = PsalmTest::extractType(types::object(\stdClass::class));
/** @psalm-check-type-exact $_existingClassObject = \stdClass */

$_classStringOfNonExistingClassObject = PsalmTest::extractType(types::classString(types::object('SomeClass')));
/** @psalm-check-type-exact $_classStringOfNonExistingClassObject = class-string */

$_classStringOfExistingClassObject = PsalmTest::extractType(types::classString(types::object(\stdClass::class)));
/** @psalm-check-type-exact $_classStringOfExistingClassObject = \stdClass::class */
--EXPECT--
