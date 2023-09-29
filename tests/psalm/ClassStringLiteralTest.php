<?php

declare(strict_types=1);

namespace Typhoon\Type\ClassStringLiteralTest;

use Typhoon\Type\ClassStringLiteralType;
use function Typhoon\Type\extractType;

/** @psalm-check-type-exact $_classLiteral = stdClass::class */
$_classLiteral = extractType(new ClassStringLiteralType(\stdClass::class));
