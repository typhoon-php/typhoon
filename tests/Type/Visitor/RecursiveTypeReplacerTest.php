<?php

declare(strict_types=1);

namespace Typhoon\Type\Visitor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\DeclarationId\Id;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Variance;
use function Typhoon\Type\stringify;

#[CoversClass(RecursiveTypeReplacer::class)]
final class RecursiveTypeReplacerTest extends TestCase
{
    /**
     * @return \Generator<Type>
     */
    private static function types(): \Generator
    {
        yield from types::cases();
        yield types::intMask(1, 2, 3);
        yield types::list();
        yield types::listShape([types::string, types::optional(types::int)]);
        yield types::unsealedListShape([types::string, types::optional(types::int)]);
        yield types::array();
        yield types::arrayShape([types::string, types::optional(types::int)]);
        yield types::unsealedArrayShape([types::string, types::optional(types::int)]);
        yield types::keyOf(types::arrayShape());
        yield types::offset(types::arrayShape(), types::int(1));
        yield types::objectShape(['a' => types::int, 'b' => types::string]);
        yield types::Generator(value: types::Generator());
        yield types::self([types::numeric]);
        yield types::parent([types::numeric]);
        yield types::static([types::numeric]);
        yield types::callable([
            types::param(types::numeric, variadic: true),
            types::param(types::numeric, hasDefault: true, byReference: true),
            types::scalar,
        ], types::array);
        yield types::classConstant(types::class(\stdClass::class), 'A');
        yield types::classConstantMask(\ArrayObject::class);
        yield types::classAlias('A', 'B', [types::int, types::string]);
        yield types::varianceAware(types::float, Variance::Contravariant);
        yield types::conditional(types::arg(Id::parameter(Id::namedFunction('a'), 'a')), types::true, types::int, types::float);
    }

    /**
     * @return \Generator<non-empty-string, array{Type}>
     */
    public static function typesProvider(): \Generator
    {
        $index = 0;

        foreach (self::types() as $type) {
            yield $index . '. ' . stringify($type) => [$type];
            ++$index;
        }
    }

    #[DataProvider('typesProvider')]
    public function testItPreservesTypesIfNothingChanges(Type $type): void
    {
        $replaced = $type->accept(new class extends RecursiveTypeReplacer {});

        self::assertSame($type, $replaced);
    }
}
