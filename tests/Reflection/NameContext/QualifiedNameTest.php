<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QualifiedName::class)]
final class QualifiedNameTest extends TestCase
{
    public function testItDoesNotAccept1Segment(): void
    {
        $this->expectExceptionObject(new InvalidName('Qualified name expects at least 2 segments, got 1'));

        new QualifiedName([new UnqualifiedName('A')]);
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

        $resolved = $qualifiedName->resolve();

        self::assertSame('A\\B', $resolved->toString());
    }

    public function testItPrependsNamespace(): void
    {
        $qualifiedName = new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]);

        $resolved = $qualifiedName->resolve(new UnqualifiedName('C'));

        self::assertSame('C\\A\\B', $resolved->toString());
    }

    public function testItReturnsNameWithFirstSegmentReplaced(): void
    {
        $qualifiedName = new QualifiedName([
            new UnqualifiedName('A'),
            new UnqualifiedName('B'),
            new UnqualifiedName('C'),
        ]);

        $resolved = $qualifiedName->resolve(importTable: ['A' => new UnqualifiedName('A1')]);

        self::assertSame('A1\\B\\C', $resolved->toString());
    }

    public function testItCorrectlyRepresentsItselfAsString(): void
    {
        $qualifiedName = new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]);

        $asString = $qualifiedName->toString();

        self::assertSame('A\\B', $asString);
    }
}
