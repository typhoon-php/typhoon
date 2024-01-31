<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelativeName::class)]
final class RelativeNameTest extends TestCase
{
    public function testItRemovesNamespacePrefixInGlobalNamespace(): void
    {
        $relativeName = new RelativeName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $resolved = $relativeName->resolve();

        self::assertSame('A\\B', $resolved->toString());
    }

    public function testItPrependsNamespaceInNamespace(): void
    {
        $relativeName = new RelativeName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $resolved = $relativeName->resolve(new UnqualifiedName('NS'));

        self::assertSame('NS\\A\\B', $resolved->toString());
    }
}
