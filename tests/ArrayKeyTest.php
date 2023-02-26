<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_arrayKey = array-key */
$_arrayKey = extractType(new ArrayKeyT());
