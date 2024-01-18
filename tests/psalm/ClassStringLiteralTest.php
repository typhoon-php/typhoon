<?php

declare(strict_types=1);

namespace Typhoon\Type\ClassStringLiteralTest;

use Typhoon\Type\ClassStringLiteralType;
use function Typhoon\Type\extractType;

/** @psalm-check-type-exact $_classStringLiteral = stdClass::class */
$_classStringLiteral = extractType(new ClassStringLiteralType(\stdClass::class));

/**
 * @param ClassStringLiteralType<\Iterator::class|\IteratorAggregate::class> $_type
 */
function testClassStringLiteralIsCovariant(ClassStringLiteralType $_type): void {}

testClassStringLiteralIsCovariant(new ClassStringLiteralType(\Iterator::class));
