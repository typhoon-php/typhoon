<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TemplateResolver::class)]
final class TemplateResolverTest extends TestCase
{
    public function testItCanBeCreatedWithoutArguments(): void
    {
        new TemplateResolver();

        $this->expectNotToPerformAssertions();
    }

    public function testItCanBeCreatedWithSelfAndResolveStaticFalse(): void
    {
        new TemplateResolver(self: 'class', resolveStatic: false);

        $this->expectNotToPerformAssertions();
    }

    public function testItCannotBeCreatedWithResolveStaticWithoutSelf(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('static cannot be resolved without self');

        new TemplateResolver(resolveStatic: true);
    }
}
