<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpDoc::class)]
#[CoversClass(PhpDocParser::class)]
final class PhpDocParserTest extends TestCase
{
    public function testDeprecatedMessageReturnsNullIfNoDeprecatedTag(): void
    {
        $parser = new PhpDocParser();

        $deprecated = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->deprecatedMessage();

        self::assertNull($deprecated);
    }

    public function testDeprecatedMessageReturnsEmptyStringIfDeprecatedWithoutMessage(): void
    {
        $parser = new PhpDocParser();

        $deprecated = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 * @deprecated   
                 */
                PHP,
        )->deprecatedMessage();

        self::assertSame('', $deprecated);
    }

    public function testDeprecatedMessageReturnsStringIfDeprecatedWithMessage(): void
    {
        $parser = new PhpDocParser();

        $deprecated = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 * @deprecated This is a God Class anti-pattern. Don't expand it.
                 */
                PHP,
        )->deprecatedMessage();

        self::assertSame("This is a God Class anti-pattern. Don't expand it.", $deprecated);
    }

    public function testHasFinalReturnsFalseIfNoFinalTag(): void
    {
        $parser = new PhpDocParser();

        $final = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->hasFinal();

        self::assertFalse($final);
    }

    public function testHasFinalReturnsTrueIfFinal(): void
    {
        $parser = new PhpDocParser();

        $final = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 * @final
                 */
                PHP,
        )->hasFinal();

        self::assertTrue($final);
    }

    public function testHasReadonlyReturnsFalseIfNoReadonlyTag(): void
    {
        $parser = new PhpDocParser();

        $readonly = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->hasReadonly();

        self::assertFalse($readonly);
    }

    public function testHasReadonlyReturnsTrueIfReadonly(): void
    {
        $parser = new PhpDocParser();

        $readonly = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 * @readonly
                 */
                PHP,
        )->hasReadonly();

        self::assertTrue($readonly);
    }

    public function testItReturnsNullVarTypeWhenNoVarTag(): void
    {
        $parser = new PhpDocParser();

        $varType = $parser->parse(
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

        $varType = $parser->parse(
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

        $paramTypes = $parser->parse(
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

        $paramTypes = $parser->parse(
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

        $returnType = $parser->parse(
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

        $returnType = $parser->parse(
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

    public function testItReturnsAllThrowsTypes(): void
    {
        $parser = new PhpDocParser();

        $throwsTypes = $parser->parse(
            <<<'PHP'
                /**
                 * @throws RuntimeException|LogicException
                 * @throws \Exception
                 * @phpstan-throws \OutOfBoundsException
                 */
                PHP,
        )->throwsTypes();

        self::assertEquals(
            [
                new UnionTypeNode([
                    new IdentifierTypeNode('RuntimeException'),
                    new IdentifierTypeNode('LogicException'),
                ]),
                new IdentifierTypeNode('\Exception'),
                new IdentifierTypeNode('\OutOfBoundsException'),
            ],
            $throwsTypes,
        );
    }

    public function testItReturnsEmptyTemplatesWhenNoTemplateTag(): void
    {
        $parser = new PhpDocParser();

        $templateTags = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->templateTags();

        self::assertEmpty($templateTags);
    }

    public function testItReturnsLatestPrioritizedTemplates(): void
    {
        $parser = new PhpDocParser();

        $templates = $parser->parse(
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
        )->templateTags();

        self::assertEquals(
            [
                '@psalm-template T of string',
                '@template T2 of mixed',
            ],
            array_map(strval(...), $templates),
        );
    }

    public function testItReturnsEmptyExtendedTypesWhenNoExtendsTag(): void
    {
        $parser = new PhpDocParser();

        $extendedTypes = $parser->parse(
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

        $extendedTypes = $parser->parse(
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

        $implementedTypes = $parser->parse(
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

        $implementedTypes = $parser->parse(
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

    public function testItReturnsEmptyUsedTypesWhenNoImplementsTag(): void
    {
        $parser = new PhpDocParser();

        $usedTypes = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->usedTypes();

        self::assertEmpty($usedTypes);
    }

    public function testItReturnsLatestPrioritizedUsedTypes(): void
    {
        $parser = new PhpDocParser();

        $usedTypes = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 *
                 * @use C<int>
                 * @use D<object>
                 * @use D<mixed>
                 * @phpstan-use C<float>
                 * @phpstan-use C<string>
                 */
                PHP,
        )->usedTypes();

        self::assertEquals(
            [
                $this->createGenericTypeNode(new IdentifierTypeNode('C'), [new IdentifierTypeNode('string')]),
                $this->createGenericTypeNode(new IdentifierTypeNode('D'), [new IdentifierTypeNode('mixed')]),
            ],
            $usedTypes,
        );
    }

    public function testItReturnsEmptyTypeAliasesWhenNoTypeTag(): void
    {
        $parser = new PhpDocParser();

        $typeAliases = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->typeAliasTags();

        self::assertEmpty($typeAliases);
    }

    public function testItReturnsLatestPrioritizedTypeAliases(): void
    {
        $parser = new PhpDocParser();

        $typeAliases = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 *
                 * @phpstan-type A = string
                 * @phpstan-type B = object
                 * @phpstan-type B = mixed
                 * @psalm-type A int
                 * @psalm-type A float
                 */
                PHP,
        )->typeAliasTags();

        self::assertSame(
            [
                '@psalm-type A float',
                '@phpstan-type B mixed',
            ],
            array_map(strval(...), $typeAliases),
        );
    }

    public function testItReturnsEmptyTypeAliasImportsWhenNoTypeTag(): void
    {
        $parser = new PhpDocParser();

        $typeAliasImports = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 */
                PHP,
        )->typeAliasImportTags();

        self::assertEmpty($typeAliasImports);
    }

    public function testItReturnsLatestPrioritizedTypeAliasImports(): void
    {
        $parser = new PhpDocParser();

        $typeAliasImports = $parser->parse(
            <<<'PHP'
                /**
                 * @example
                 *
                 * @phpstan-import-type A from string
                 * @phpstan-import-type B from object
                 * @phpstan-import-type B from mixed
                 * @psalm-import-type A from int
                 * @psalm-import-type A from float
                 * @psalm-import-type C from bool as A
                 */
                PHP,
        )->typeAliasImportTags();

        self::assertSame(
            [
                '@psalm-import-type C from bool as A',
                '@phpstan-import-type B from mixed',
            ],
            array_map(strval(...), $typeAliasImports),
        );
    }

    public function testItCachesPriority(): void
    {
        $tagPrioritizer = $this->createMock(PhpDocTagPrioritizer::class);
        $tagPrioritizer->expects(self::exactly(3))->method('priorityFor')->willReturn(0);
        $parser = new PhpDocParser(tagPrioritizer: $tagPrioritizer);

        $parser->parse(
            <<<'PHP'
                /**
                 * @param string $a
                 * @param string $a
                 * @param string $a
                 */
                PHP,
        )->paramTypes();
    }

    public function testItMovesLine(): void
    {
        $parser = new PhpDocParser();

        $tag = $parser
            ->parse(
                phpDoc: <<<'PHP'
                    /**
                     * @psalm-type A = string
                     */
                    PHP,
                startLine: 2,
            )
            ->typeAliasTags()[0];

        self::assertSame($tag->getAttribute('startLine'), 3);
        self::assertSame($tag->getAttribute('endLine'), 3);
    }

    public function testItCalculatesPosition(): void
    {
        $parser = new PhpDocParser();

        $tag = $parser
            ->parse(
                phpDoc: <<<'PHP'
                    /**
                     * @psalm-type A = string
                     */
                    PHP,
            )
            ->typeAliasTags()[0];

        self::assertSame(PhpDocParser::startPosition($tag), 7);
        self::assertSame(PhpDocParser::endPosition($tag), 29);
    }

    public function testItMovesPosition(): void
    {
        $parser = new PhpDocParser();

        $tag = $parser
            ->parse(
                phpDoc: <<<'PHP'
                    /**
                     * @psalm-type A = string
                     */
                    PHP,
                startPosition: 10,
            )
            ->typeAliasTags()[0];

        self::assertSame(PhpDocParser::startPosition($tag), 17);
        self::assertSame(PhpDocParser::endPosition($tag), 39);
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
