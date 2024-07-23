<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $constants = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                final class A
                {
                    const NOT_DEPRECATED = 1;
                    /** @deprecated */
                    const DEPRECATED = 1;
                    /** @deprecated Message */
                    const DEPRECATED_WITH_MESSAGE = 1;
                }
                PHP,
        ))
        ->reflectClass('A')
        ->constants();

    assertFalse($constants['NOT_DEPRECATED']->isDeprecated());
    assertNull($constants['NOT_DEPRECATED']->deprecation());
    assertTrue($constants['DEPRECATED']->isDeprecated());
    assertEquals(new Deprecation(), $constants['DEPRECATED']->deprecation());
    assertTrue($constants['DEPRECATED_WITH_MESSAGE']->isDeprecated());
    assertEquals(new Deprecation('Message'), $constants['DEPRECATED_WITH_MESSAGE']->deprecation());
};
