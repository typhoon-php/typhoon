<?php

declare(strict_types=1);

namespace Typhoon\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parameter::class)]
final class ParameterTest extends TestCase
{
    public function testDefaults(): void
    {
        $parameter = new Parameter();

        self::assertSame(types::mixed, $parameter->type);
        self::assertFalse($parameter->hasDefault);
        self::assertFalse($parameter->variadic);
        self::assertFalse($parameter->byReference);
    }

    public function testWith(): void
    {
        $parameter = new Parameter();

        $newParameter = $parameter->with(
            type: types::bool,
            hasDefault: true,
            variadic: true,
            byReference: true,
        );

        self::assertSame(types::bool, $newParameter->type);
        self::assertTrue($newParameter->hasDefault);
        self::assertTrue($newParameter->variadic);
        self::assertTrue($newParameter->byReference);
    }
}
