<?php

declare(strict_types=1);

namespace Typhoon\Type;

/** @psalm-check-type-exact $_arrayKey = array-key */
$_arrayKey = extractType(ArrayKeyType::type);
