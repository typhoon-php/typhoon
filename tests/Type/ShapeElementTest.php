<?php

declare(strict_types=1);

namespace Typhoon\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShapeElement::class)]
final class ShapeElementTest extends TestCase
{
    public function testDefaults(): void
    {
        $element = new ShapeElement();

        self::assertSame(types::mixed, $element->type);
        self::assertFalse($element->optional);
    }

    public function testWith(): void
    {
        $element = new ShapeElement();

        $newElement = $element->with(
            type: types::bool,
            optional: true,
        );

        self::assertSame(types::bool, $newElement->type);
        self::assertTrue($newElement->optional);
    }
}
