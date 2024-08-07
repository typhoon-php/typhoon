<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use function PHPUnit\Framework\assertSame;

return static function (TyphoonReflector $reflector): void {
    $value = $reflector->reflectConstant('PHP_INT_MIN')->evaluate();

    assertSame(PHP_INT_MIN, $value);
};
