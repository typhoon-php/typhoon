<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NameContext::class)]
final class NameContextTest extends TestCase
{
    public function testItThrowsOnEmptyName(): void
    {
        $nameContext = new NameContext();

        $this->expectExceptionObject(new InvalidName('Empty name'));

        $nameContext->resolveNameAsClass('');
    }
}
