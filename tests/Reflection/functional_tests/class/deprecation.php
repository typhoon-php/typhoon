<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $reflector = $reflector->withResource(Resource::fromCode(
        <<<'PHP'
            <?php
            final class NotDeprecated {}

            /** @deprecated */
            final class Deprecated {}

            /** @deprecated Message */
            final class DeprecatedWithMessage {}
            PHP,
    ));

    assertFalse($reflector->reflectClass('NotDeprecated')->isDeprecated());
    assertNull($reflector->reflectClass('NotDeprecated')->deprecation());

    assertTrue($reflector->reflectClass('Deprecated')->isDeprecated());
    assertEquals(new Deprecation(), $reflector->reflectClass('Deprecated')->deprecation());

    assertTrue($reflector->reflectClass('DeprecatedWithMessage')->isDeprecated());
    assertEquals(new Deprecation('Message'), $reflector->reflectClass('DeprecatedWithMessage')->deprecation());
};
