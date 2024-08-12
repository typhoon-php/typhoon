<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $iterator = $reflector->reflectClass(\Iterator::class);
    assertEquals(
        [types::classTemplate(\Iterator::class, 'TKey'), types::classTemplate(\Iterator::class, 'TValue')],
        $iterator->typeArgumentsOf(\Iterator::class),
    );
    assertEquals(
        [types::classTemplate(\Iterator::class, 'TKey'), types::classTemplate(\Iterator::class, 'TValue')],
        $iterator->typeArgumentsOf(\Traversable::class),
    );

    $a = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php

                /**
                 * @implements Iterator<string>
                 */
                abstract class A implements Iterator {}
                PHP,
        ))
        ->reflectClass('A');
    assertEquals(
        [types::mixed, types::string],
        $a->typeArgumentsOf(\Iterator::class),
    );
    assertEquals(
        [types::mixed, types::string],
        $a->typeArgumentsOf(\Traversable::class),
    );
};
