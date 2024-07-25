<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KeyIsNotDefined::class)]
final class KeyIsNotDefinedTest extends TestCase
{
    public function testItEscapesDoubleQuotesInKey(): void
    {
        $exception = new KeyIsNotDefined('"\'"');

        self::assertSame($exception->getMessage(), 'Key "\"\'\"" is not defined in the Collection');
    }
}
