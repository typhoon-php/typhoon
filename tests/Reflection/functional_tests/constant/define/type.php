<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

return static function (TyphoonReflector $reflector): void {
    $constant = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                
                namespace X;
                
                define('Y\\A', 123);
                PHP,
        ))
        ->reflectConstant('Y\A');

    assertEquals(types::int(123), $constant->type());
    assertEquals(types::int(123), $constant->type(TypeKind::Inferred));
    assertNull($constant->type(TypeKind::Native));
    assertNull($constant->type(TypeKind::Tentative));
    assertNull($constant->type(TypeKind::Annotated));
};
