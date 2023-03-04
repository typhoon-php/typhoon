<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_classString = class-string<\stdClass> */
$_classString = extractType(new NamedClassStringType(new NamedObjectType(\stdClass::class)));
