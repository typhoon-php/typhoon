<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_int = int */
$_int = extractType(new IntRangeT());
