<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

return static function (TyphoonReflector $reflector): void {
    $constant = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                
                namespace X;
                
                /**
                 * @var positive-int
                 */
                const A = 123;
                PHP,
        ))
        ->reflectConstant('X\A');

    assertSame(types::positiveInt, $constant->type());
    assertEquals(types::int(123), $constant->type(TypeKind::Inferred));
    assertNull($constant->type(TypeKind::Native));
    assertNull($constant->type(TypeKind::Tentative));
    assertSame(types::positiveInt, $constant->type(TypeKind::Annotated));
};
