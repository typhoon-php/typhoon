<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\NameResolution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QualifiedName::class)]
final class QualifiedNameTest extends TestCase
{
    public function testItDoesNotAccept1Segment(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Qualified name expects at least 2 segments, got 1.'));

        new QualifiedName([new UnqualifiedName('A')]);
    }

    public function testItReturnsFirstSegment(): void
    {
        $qualifiedName = new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]);

        $firstSegment = $qualifiedName->firstSegment();

        self::assertSame('A', $firstSegment->toString());
    }

    public function testItReturnsLastSegment(): void
    {
        $qualifiedName = new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]);

        $lastSegment = $qualifiedName->lastSegment();

        self::assertSame('B', $lastSegment->toString());
    }

    public function testItResolvesToItselfInGlobalNamespace(): void
    {
        $qualifiedName = new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]);

        $resolved = $qualifiedName->resolveInNamespace();

        self::assertSame('A\\B', $resolved->toString());
    }

    public function testItPrependsNamespace(): void
    {
        $qualifiedName = new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]);

        $resolved = $qualifiedName->resolveInNamespace(new UnqualifiedName('C'));

        self::assertSame('C\\A\\B', $resolved->toString());
    }

    public function testItReturnsNameWithFirstSegmentReplaced(): void
    {
        $qualifiedName = new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
            new UnqualifiedName('C'),
        ]);

        $withFirstSegmentReplaced = $qualifiedName->withFirstSegmentReplaced(new UnqualifiedName('A1'));

        self::assertSame('A1\\B\\C', $withFirstSegmentReplaced->toString());
    }

    public function testItCorrectlyRepresentsItselfAsString(): void
    {
        $qualifiedName = new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]);

        $asString = $qualifiedName->toString();

        self::assertSame('A\\B', $asString);
    }
}
