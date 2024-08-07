<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

return static function (TyphoonReflector $reflector): void {
    $object = new class {};

    $id = $reflector->reflectClass($object::class)->id;

    assertInstanceOf(AnonymousClassId::class, $id);
    assertSame(__FILE__, $id->file);
    assertSame(13, $id->line);
    assertNull($id->column);
    assertSame($object::class, $id->name);
};
