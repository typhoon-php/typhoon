<?php

declare(strict_types=1);

namespace Typhoon\Type;

/** @psalm-check-type-exact $_literalString = literal-string */
$_literalString = extractType(LiteralStringType::type);
