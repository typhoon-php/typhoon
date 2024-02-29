<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FullyQualifiedName::class)]
final class FullyQualifiedNameTest extends TestCase
{
    public function testItRemovesNamespacePrefixInGlobalNamespace(): void
    {
        $relativeName = new FullyQualifiedName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $resolved = $relativeName->resolve();

        self::assertSame('A\\B', $resolved->toString());
    }
}
