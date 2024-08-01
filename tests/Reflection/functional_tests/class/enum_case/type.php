<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

return static function (TyphoonReflector $reflector): void {
    $reflection = $reflector
        ->withResource(Resource::fromCode('<?php enum A { case X; }'))
        ->reflectClass('A')
        ->enumCases()['X'];

    assertNull($reflection->type(TypeKind::Native));
    assertEquals(types::classConstant('A', 'X'), $reflection->type());
    assertNull($reflection->type(TypeKind::Native));
    assertNull($reflection->type(TypeKind::Annotated));
    assertEquals(types::classConstant('A', 'X'), $reflection->type(TypeKind::Inferred));
};
