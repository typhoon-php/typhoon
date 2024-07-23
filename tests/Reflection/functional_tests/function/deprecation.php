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
            function notDeprecated () {}

            /** @deprecated */
            function deprecated () {}

            /** @deprecated Message */
            function deprecatedWithMessage () {}
            PHP,
    ));

    assertFalse($reflector->reflectFunction('notDeprecated')->isDeprecated());
    assertNull($reflector->reflectFunction('notDeprecated')->deprecation());

    assertTrue($reflector->reflectFunction('deprecated')->isDeprecated());
    assertEquals(new Deprecation(), $reflector->reflectFunction('deprecated')->deprecation());

    assertTrue($reflector->reflectFunction('deprecatedWithMessage')->isDeprecated());
    assertEquals(new Deprecation('Message'), $reflector->reflectFunction('deprecatedWithMessage')->deprecation());
};
