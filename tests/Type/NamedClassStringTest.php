<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_classString = class-string<\stdClass> */
$_classString = extractType(new NamedClassStringT(new NamedObjectT(\stdClass::class)));
