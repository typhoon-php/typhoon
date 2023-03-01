<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_stdClass = \stdClass */
$_stdClass = extractType(new NamedObjectT(\stdClass::class));

/** @var NamedObjectT<\ArrayObject<int, string>> */
$arrayObjectType = new NamedObjectT(\ArrayObject::class, new IntT(), new StringT());
/** @psalm-check-type-exact $_arrayObject = \ArrayObject<int, string> */
$_arrayObject = extractType($arrayObjectType);

function testObjectIsCovariant(NamedObjectT $_type): void
{
}

testObjectIsCovariant(new NamedObjectT(\stdClass::class));
