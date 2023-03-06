<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

/** @psalm-check-type-exact $_true = true */
$_true = extractType(TrueType::type);
