<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\NameResolution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Name::class)]
final class NameTest extends TestCase
{
    /**
     * @return \Generator<int, array{string, Name}>
     */
    public static function validNames(): \Generator
    {
        yield ['a', new UnqualifiedName('a')];
        yield ['A', new UnqualifiedName('A')];
        yield ['A1234', new UnqualifiedName('A1234')];
        yield ['Привет', new UnqualifiedName('Привет')];
        yield ['A\\B', new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')])];
        yield ['\\A', new FullyQualifiedName(new UnqualifiedName('A'))];
        yield ['\\A\\B', new FullyQualifiedName(new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]))];
        yield ['namespace\\A\\B', new RelativeName(new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]))];
    }

    /**
     * @return \Generator<int, array{list<null|UnqualifiedName|QualifiedName>, string}>
     */
    public static function concatenations(): \Generator
    {
        yield [[new UnqualifiedName('A')], 'A'];
        yield [[new UnqualifiedName('A'), null], 'A'];
        yield [[null, new UnqualifiedName('A')], 'A'];
        yield [[new UnqualifiedName('A'), new UnqualifiedName('B')], 'A\\B'];
        yield [[new UnqualifiedName('A'), new UnqualifiedName('B'), new UnqualifiedName('C')], 'A\\B\\C'];
        yield [[new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')])], 'A\\B'];
        yield [[new QualifiedName([new UnqualifiedName('A'), new UnqualifiedName('B')]), new UnqualifiedName('C')], 'A\\B\\C'];
        yield [[new UnqualifiedName('A'), new QualifiedName([new UnqualifiedName('B'), new UnqualifiedName('C')])], 'A\\B\\C'];
    }

    public function testItIsReturnsNullForNullName(): void
    {
        $nameObject = Name::fromString(null);

        self::assertNull($nameObject);
    }

    #[DataProvider('validNames')]
    public function testItIsCorrectlyCreatedFromString(string $name, Name $expectedName): void
    {
        $nameObject = Name::fromString($name);

        self::assertEquals($expectedName, $nameObject);
    }

    /**
     * @param list<null|UnqualifiedName|QualifiedName> $segments
     */
    #[DataProvider('concatenations')]
    public function testItCorrectlyConcatenatesSegments(array $segments, string $expectedName): void
    {
        $name = Name::concatenate(...$segments);

        self::assertSame($expectedName, $name->toString());
    }

    public function testItThrowsIfNothingToConcatenate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Nothing to concatenate.'));

        Name::concatenate(null, null);
    }
}
