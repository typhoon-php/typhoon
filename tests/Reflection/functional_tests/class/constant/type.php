<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

return static function (TyphoonReflector $reflector): void {
    $constants = $reflector
        ->withResource(Resource::fromCode(<<<'PHP'
            <?php
            class A
            {
                const WITHOUT_TYPE = 1;
                const int WITH_NATIVE_TYPE = 1;
                
                /**
                 * @var positive-int
                 */
                const int WITH_PHPDOC = 1;
            }
            PHP))
        ->reflectClass('A')
        ->constants();

    assertEquals(types::int(1), $constants['WITHOUT_TYPE']->type());
    assertNull($constants['WITHOUT_TYPE']->type(TypeKind::Native));
    assertNull($constants['WITHOUT_TYPE']->type(TypeKind::Annotated));
    assertEquals(types::int(1), $constants['WITHOUT_TYPE']->type(TypeKind::Inferred));

    assertEquals(types::int(1), $constants['WITH_NATIVE_TYPE']->type());
    assertEquals(types::int, $constants['WITH_NATIVE_TYPE']->type(TypeKind::Native));
    assertNull($constants['WITH_NATIVE_TYPE']->type(TypeKind::Annotated));
    assertEquals(types::int(1), $constants['WITH_NATIVE_TYPE']->type(TypeKind::Inferred));

    assertEquals(types::positiveInt, $constants['WITH_PHPDOC']->type());
    assertEquals(types::int, $constants['WITH_PHPDOC']->type(TypeKind::Native));
    assertEquals(types::positiveInt, $constants['WITH_PHPDOC']->type(TypeKind::Annotated));
    assertEquals(types::int(1), $constants['WITH_PHPDOC']->type(TypeKind::Inferred));
};
