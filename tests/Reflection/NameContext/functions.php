<?php

declare(strict_types=1);

use Typhoon\Reflection\NameContext\NameContextFunctionalTester;

function check(mixed $value): void
{
    NameContextFunctionalTester::record($value);
}

function defineConst(string $name): void
{
    define($name, $name);
}
