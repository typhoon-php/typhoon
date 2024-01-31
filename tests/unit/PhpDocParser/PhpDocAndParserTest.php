<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\Variance;

#[CoversClass(PhpDoc::class)]
#[CoversClass(PhpDocParser::class)]
final class PhpDocAndParserTest extends TestCase
{
    public function testPhpDocEmptyReturnsSame(): void
    {
        $phpDoc1 = PhpDoc::empty();
        $phpDoc2 = PhpDoc::empty();

        self::assertSame($phpDoc1, $phpDoc2);
    }

    public function testPhpDocTemplateTagVariance(): void
    {
        $parser = new PhpDocParser();
        $templates = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @template-covariant T
                 */
                PHP,
        )->templates();
        self::assertCount(1, $templates);

        $actualVariance = PhpDoc::templateTagVariance($templates[0]);

        self::assertSame(Variance::COVARIANT, $actualVariance);
    }

    public function testIsDeprecatedReturnsFalseIfNoDeprecatedTag(): void
    {
        $parser = new PhpDocParser();

        $deprecated = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->isDeprecated();

        self::assertFalse($deprecated);
    }

    public function testIsDeprecatedReturnsTrueIfDeprecated(): void
    {
        $parser = new PhpDocParser();

        $deprecated = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @deprecated
                 */
                PHP,
        )->isDeprecated();

        self::assertTrue($deprecated);
    }

    public function testIsFinalReturnsFalseIfNoFinalTag(): void
    {
        $parser = new PhpDocParser();

        $final = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->isFinal();

        self::assertFalse($final);
    }

    public function testIsFinalReturnsTrueIfFinal(): void
    {
        $parser = new PhpDocParser();

        $final = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @final
                 */
                PHP,
        )->isFinal();

        self::assertTrue($final);
    }

    public function testIsReadonlyReturnsFalseIfNoReadonlyTag(): void
    {
        $parser = new PhpDocParser();

        $readonly = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->isReadonly();

        self::assertFalse($readonly);
    }

    public function testIsReadonlyReturnsTrueIfReadonly(): void
    {
        $parser = new PhpDocParser();

        $readonly = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @readonly
                 */
                PHP,
        )->isReadonly();

        self::assertTrue($readonly);
    }

    public function testItReturnsNullVarTypeWhenNoVarTag(): void
    {
        $parser = new PhpDocParser();

        $varType = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->varType();

        self::assertNull($varType);
    }

    public function testItReturnsLatestPrioritizedVarTagType(): void
    {
        $parser = new PhpDocParser();

        $varType = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @var int
                 * @psalm-var float
                 * @psalm-var string
                 */
                PHP,
        )->varType();

        self::assertEquals(new IdentifierTypeNode('string'), $varType);
    }

    public function testItReturnsNullParamTypeWhenNoParamTag(): void
    {
        $parser = new PhpDocParser();

        $paramTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->paramTypes();

        self::assertEmpty($paramTypes);
    }

    public function testItReturnsLatestPrioritizedParamTagType(): void
    {
        $parser = new PhpDocParser();

        $paramTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @param int $a
                 * @param object $b
                 * @param mixed $b
                 * @psalm-param float $a
                 * @psalm-param string $a
                 */
                PHP,
        )->paramTypes();

        self::assertEquals(
            [
                'a' => new IdentifierTypeNode('string'),
                'b' => new IdentifierTypeNode('mixed'),
            ],
            $paramTypes,
        );
    }

    public function testItReturnsNullReturnTypeWhenNoReturnTag(): void
    {
        $parser = new PhpDocParser();

        $returnType = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->returnType();

        self::assertNull($returnType);
    }

    public function testItReturnsLatestPrioritizedReturnTagType(): void
    {
        $parser = new PhpDocParser();

        $returnType = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @return int
                 * @psalm-return float
                 * @psalm-return string
                 */
                PHP,
        )->returnType();

        self::assertEquals(new IdentifierTypeNode('string'), $returnType);
    }

    public function testItReturnsEmptyTemplatesWhenNoTemplateTag(): void
    {
        $parser = new PhpDocParser();

        $templates = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->templates();

        self::assertEmpty($templates);
    }

    public function testItReturnsLatestPrioritizedTemplates(): void
    {
        $parser = new PhpDocParser();

        $templates = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * @template T of int
                 * @template T2 of object
                 * @template T2 of mixed
                 * @psalm-template T of float
                 * @psalm-template T of string
                 */
                PHP,
        )->templates();

        self::assertEquals(
            [
                $this->createTemplateTagValueNode('T', new IdentifierTypeNode('string'), Variance::INVARIANT),
                $this->createTemplateTagValueNode('T2', new IdentifierTypeNode('mixed'), Variance::INVARIANT),
            ],
            $templates,
        );
    }

    public function testItAddsVarianceAttributeToTemplates(): void
    {
        $parser = new PhpDocParser();

        $templates = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @template TInvariant
                 * @template-covariant TCovariant
                 * @template-contravariant TContravariant
                 */
                PHP,
        )->templates();

        self::assertEquals(
            [
                $this->createTemplateTagValueNode('TInvariant', null, Variance::INVARIANT),
                $this->createTemplateTagValueNode('TCovariant', null, Variance::COVARIANT),
                $this->createTemplateTagValueNode('TContravariant', null, Variance::CONTRAVARIANT),
            ],
            $templates,
        );
    }

    public function testItReturnsEmptyExtendedTypesWhenNoExtendsTag(): void
    {
        $parser = new PhpDocParser();

        $extendedTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->extendedTypes();

        self::assertEmpty($extendedTypes);
    }

    public function testItReturnsLatestPrioritizedExtendedTypes(): void
    {
        $parser = new PhpDocParser();

        $extendedTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * 
                 * @extends C<int>
                 * @extends D<object>
                 * @extends D<mixed>
                 * @phpstan-extends C<float>
                 * @phpstan-extends C<string>
                 */
                PHP,
        )->extendedTypes();

        self::assertEquals(
            [
                $this->createGenericTypeNode(new IdentifierTypeNode('C'), [new IdentifierTypeNode('string')]),
                $this->createGenericTypeNode(new IdentifierTypeNode('D'), [new IdentifierTypeNode('mixed')]),
            ],
            $extendedTypes,
        );
    }

    public function testItReturnsEmptyImplementedTypesWhenNoImplementsTag(): void
    {
        $parser = new PhpDocParser();

        $implementedTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->implementedTypes();

        self::assertEmpty($implementedTypes);
    }

    public function testItReturnsLatestPrioritizedImplementedTypes(): void
    {
        $parser = new PhpDocParser();

        $implementedTypes = $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @example
                 * 
                 * @implements C<int>
                 * @implements D<object>
                 * @implements D<mixed>
                 * @phpstan-implements C<float>
                 * @phpstan-implements C<string>
                 */
                PHP,
        )->implementedTypes();

        self::assertEquals(
            [
                $this->createGenericTypeNode(new IdentifierTypeNode('C'), [new IdentifierTypeNode('string')]),
                $this->createGenericTypeNode(new IdentifierTypeNode('D'), [new IdentifierTypeNode('mixed')]),
            ],
            $implementedTypes,
        );
    }

    public function testItCachesPriority(): void
    {
        $tagPrioritizer = $this->createMock(TagPrioritizer::class);
        $tagPrioritizer->expects(self::exactly(3))->method('priorityFor')->willReturn(0);
        $parser = new PhpDocParser(tagPrioritizer: $tagPrioritizer);

        $parser->parsePhpDoc(
            <<<'PHP'
                /**
                 * @param string $a
                 * @param string $a
                 * @param string $a
                 */
                PHP,
        )->paramTypes();
    }

    public function testMethodsMemoized(): void
    {
        $phpDoc = (new PhpDocParser())->parsePhpDoc(
            <<<'PHP'
                /**
                 * @template T
                 * @implements Iterator
                 * @extends stdClass
                 * @var string
                 * @param int $x
                 * @param float $y
                 * @return array
                 */
                PHP,
        );
        $tags = static fn(): array => [
            $phpDoc->templates(),
            $phpDoc->implementedTypes(),
            $phpDoc->extendedTypes(),
            $phpDoc->varType(),
            $phpDoc->paramTypes(),
            $phpDoc->returnType(),
        ];

        $first = $tags();
        $second = $tags();

        self::assertSame($first, $second);
    }

    private function createTemplateTagValueNode(string $name, ?TypeNode $bound, Variance $variance): TemplateTagValueNode
    {
        $template = new TemplateTagValueNode($name, $bound, '');
        $template->setAttribute('variance', $variance);

        return $template;
    }

    /**
     * @param list<TypeNode> $genericTypes
     */
    private function createGenericTypeNode(IdentifierTypeNode $type, array $genericTypes): GenericTypeNode
    {
        return new GenericTypeNode(
            type: $type,
            genericTypes: $genericTypes,
            variances: array_fill(0, \count($genericTypes), GenericTypeNode::VARIANCE_INVARIANT),
        );
    }
}
