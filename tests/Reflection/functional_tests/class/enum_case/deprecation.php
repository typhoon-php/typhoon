<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $cases = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                enum A
                {
                    case NotDeprecated = 1;
                    /** @deprecated */
                    case Deprecated = 1;
                    /** @deprecated Message */
                    case DeprecatedWithMessage = 1;
                }
                PHP,
        ))
        ->reflectClass('A')
        ->enumCases();

    assertFalse($cases['NotDeprecated']->isDeprecated());
    assertNull($cases['NotDeprecated']->deprecation());
    assertTrue($cases['Deprecated']->isDeprecated());
    assertEquals(new Deprecation(), $cases['Deprecated']->deprecation());
    assertTrue($cases['DeprecatedWithMessage']->isDeprecated());
    assertEquals(new Deprecation('Message'), $cases['DeprecatedWithMessage']->deprecation());
};
