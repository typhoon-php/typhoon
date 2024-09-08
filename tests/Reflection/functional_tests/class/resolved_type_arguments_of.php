<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $iterator = $reflector->reflectClass(\Iterator::class);
    assertEquals([types::mixed, types::mixed], $iterator->resolvedTypeArgumentsOf(\Iterator::class));
    assertEquals([types::int, types::string], $iterator->resolvedTypeArgumentsOf(\Iterator::class, [types::int, types::string]));
    assertEquals([types::mixed, types::mixed], $iterator->resolvedTypeArgumentsOf(\Traversable::class));
    assertEquals([types::int, types::string], $iterator->resolvedTypeArgumentsOf(\Traversable::class, [types::int, types::string]));

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
    assertEquals([types::mixed, types::string], $a->resolvedTypeArgumentsOf(\Iterator::class));
    assertEquals([types::mixed, types::string], $a->resolvedTypeArgumentsOf(\Traversable::class));
};
