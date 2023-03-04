<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_int = int */
$_int = extractType(new IntRangeType());
