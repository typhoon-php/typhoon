<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\NameResolution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelativeName::class)]
final class RelativeNameTest extends TestCase
{
    public function testItCorrectlyRepresentsItSelfAsString(): void
    {
        $relativeName = new RelativeName(new UnqualifiedName('A'));

        $asString = $relativeName->toString();

        self::assertSame('namespace\\A', $asString);
    }

    public function testItReturnsLastSegmentOfUnqualifiedName(): void
    {
        $relativeName = new RelativeName(new UnqualifiedName('A'));

        $lastSegment = $relativeName->lastSegment();

        self::assertSame('A', $lastSegment->toString());
    }

    public function testItReturnsLastSegmentOfQualifiedName(): void
    {
        $relativeName = new RelativeName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $lastSegment = $relativeName->lastSegment();

        self::assertSame('B', $lastSegment->toString());
    }

    public function testItRemovesNamespacePrefixInGlobalNamespace(): void
    {
        $relativeName = new RelativeName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $resolved = $relativeName->resolveInNamespace();

        self::assertSame('A\\B', $resolved->toString());
    }

    public function testItPrependsNamespaceInNamespace(): void
    {
        $relativeName = new RelativeName(new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
        ]));

        $resolved = $relativeName->resolveInNamespace(new UnqualifiedName('NS'));

        self::assertSame('NS\\A\\B', $resolved->toString());
    }
}
