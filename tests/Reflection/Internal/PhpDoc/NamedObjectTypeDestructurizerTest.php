<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\DeclarationId\Id;
use Typhoon\Type\types;

#[CoversClass(NamedObjectTypeDestructurizer::class)]
final class NamedObjectTypeDestructurizerTest extends TestCase
{
    public function testItThrowsForOtherTypes(): void
    {
        $destructurized = types::object->accept(new NamedObjectTypeDestructurizer());

        self::assertNull($destructurized);
    }

    public function testItDestructuresNamedObject(): void
    {
        $type = types::object(\ArrayAccess::class, [types::int, types::string]);

        $destructurized = $type->accept(new NamedObjectTypeDestructurizer());

        self::assertEquals(
            [Id::namedClass(\ArrayAccess::class), [types::int, types::string]],
            $destructurized,
        );
    }
}
