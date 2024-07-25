<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

return static function (TyphoonReflector $reflector): void {
    $method = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                /** 
                 * @method T2 m<T, T2>(T $t)
                 */
                final class A {}
                PHP,
        ))
        ->reflectClass('A')
        ->methods()['m'];

    assertSame($method->templates()->keys(), ['T', 'T2']);
    assertEquals(types::methodTemplate('A', 'm', 'T2'), $method->returnType());
    assertEquals(types::methodTemplate('A', 'm', 'T'), $method->parameters()['t']->type());
};
