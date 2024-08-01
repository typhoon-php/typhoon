<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;

return static function (TyphoonReflector $reflector): void {
    $reflection = $reflector->withResource(Resource::fromCode(
        <<<'PHP'
            <?php

            final class A
            {
                /** @var array<string> */
                public $array;
                
                /** @var iterable<string> */
                public $iterable;
                
                /** @var Traversable<string> */
                public $Traversable;
                
                /** @var Iterator<string> */
                public $Iterator;
                
                /** @var IteratorAggregate<string> */
                public $IteratorAggregate;
                
                /** @var Generator<string> */
                public $Generator;
            }
            PHP,
    ))->reflectClass('A');

    assertEquals(types::array(value: types::string), $reflection->properties()['array']->type());
    assertEquals(types::iterable(value: types::string), $reflection->properties()['iterable']->type());
    assertEquals(types::object(\Traversable::class, [types::mixed, types::string]), $reflection->properties()['Traversable']->type());
    assertEquals(types::object(\Iterator::class, [types::mixed, types::string]), $reflection->properties()['Iterator']->type());
    assertEquals(types::object(\IteratorAggregate::class, [types::mixed, types::string]), $reflection->properties()['IteratorAggregate']->type());
    assertEquals(types::Generator(value: types::string), $reflection->properties()['Generator']->type());
};
