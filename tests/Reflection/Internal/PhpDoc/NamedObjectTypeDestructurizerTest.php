<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\Type\types;

#[CoversClass(NamedObjectTypeDestructurizer::class)]
final class NamedObjectTypeDestructurizerTest extends TestCase
{
    public function testItThrowsForOtherTypes(): void
    {
        $this->expectExceptionObject(new \LogicException());

        types::object->accept(new NamedObjectTypeDestructurizer());
    }

    public function testItDestructuresNamedObject(): void
    {
        $type = types::object(\ArrayAccess::class, [types::int, types::string]);

        [$class, $typeArguments] = $type->accept(new NamedObjectTypeDestructurizer());

        self::assertSame(\ArrayAccess::class, $class->name);
        self::assertSame([types::int, types::string], $typeArguments);
    }
}
