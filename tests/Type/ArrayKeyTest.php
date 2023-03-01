<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_arrayKey = array-key */
$_arrayKey = extractType(new ArrayKeyT());
