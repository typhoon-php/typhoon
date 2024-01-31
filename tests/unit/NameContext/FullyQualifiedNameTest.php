<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FullyQualifiedName::class)]
final class FullyQualifiedNameTest extends TestCase
{
    public function testItCorrectlyRepresentsItSelfAsString(): void
    {
        $relativeName = new FullyQualifiedName(new UnqualifiedName('A'));

        $asString = $relativeName->toString();

        self::assertSame('\\A', $asString);
    }

    public function testItReturnsLastSegmentOfUnqualifiedName(): void
    {
        $relativeName = new FullyQualifiedName(new UnqualifiedName('A'));

        $lastSegment = $relativeName->lastSegment();

        self::assertSame('A', $lastSegment->toString());
    }

    public function testItReturnsLastSegmentOfQualifiedName(): void
    {
        $relativeName = new FullyQualifiedName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $lastSegment = $relativeName->lastSegment();

        self::assertSame('B', $lastSegment->toString());
    }

    public function testItRemovesNamespacePrefixInGlobalNamespace(): void
    {
        $relativeName = new FullyQualifiedName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $resolved = $relativeName->resolveInNamespace();

        self::assertSame('A\\B', $resolved->toString());
    }

    public function testItDoesNotPrependNamespaceWhenResolving(): void
    {
        $relativeName = new FullyQualifiedName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $resolved = $relativeName->resolveInNamespace(new UnqualifiedName('NS'));

        self::assertSame('A\\B', $resolved->toString());
    }
}
