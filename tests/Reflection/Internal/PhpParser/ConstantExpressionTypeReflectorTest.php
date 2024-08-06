<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Expression as StmtExpr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Reflection\Internal\Context\ContextVisitor;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(ConstantExpressionTypeReflector::class)]
final class ConstantExpressionTypeReflectorTest extends TestCase
{
    private static ?Parser $parser = null;

    public function testItReturnsNullForNullExpression(): void
    {
        $reflector = new ConstantExpressionTypeReflector(Context::start(''));

        $type = $reflector->reflect(null);

        self::assertNull($type);
    }

    /**
     * @return \Generator<array-key, array{string, Type}>
     */
    public static function basicExpressions(): \Generator
    {
        yield ['null', types::null];
        yield ['true', types::true];
        yield ['false', types::false];
        yield ['PHP_INT_MIN', types::constant('PHP_INT_MIN')];
        yield ['\PHP_INT_MIN', types::constant('PHP_INT_MIN')];
        yield ['1', types::int(1)];
        yield ["'1'", types::string('1')];
        yield ['1 + 1', types::int(2)];
        yield ['1 - 1', types::int(0)];
        yield ['1 . 1', types::string('11')];
        yield ['[]', types::arrayShape()];
        yield ['[1,2]', types::arrayShape([types::int(1), types::int(2)])];
        yield ["['a' => 1]", types::arrayShape(['a' => types::int(1)])];
        yield ["['a' => [1]]['a']", types::arrayShape([types::int(1)])];
        yield ['new stdClass', types::object(\stdClass::class)];
        yield ["new ('std'.'Class')", types::object(\stdClass::class)];
        yield ['ArrayObject::class', types::class(\ArrayObject::class)];
        yield ['ArrayObject::STD_PROP_LIST', types::classConstant(\ArrayObject::class, 'STD_PROP_LIST')];
        yield ["ArrayObject::{'STD_PROP'.'_LIST'}", types::classConstant(\ArrayObject::class, 'STD_PROP_LIST')];
    }

    #[DataProvider('basicExpressions')]
    public function testItReflectsBasicExpressions(string $code, Type $expectedType): void
    {
        $types = $this->reflect(
            "<?php {$code};",
            static fn(Node $node): \Generator => $node instanceof StmtExpr ? yield $node->expr : null,
        );

        self::assertEquals([$expectedType], $types);
    }

    public function testItReflectsImportedGlobalConstantInNamespaceAsConstantType(): void
    {
        $types = $this->reflect(
            '<?php namespace X; use const PHP_INT_MIN; PHP_INT_MIN;',
            static fn(Node $node): \Generator => $node instanceof StmtExpr ? yield $node->expr : null,
        );

        self::assertEquals([types::constant('PHP_INT_MIN')], $types);
    }

    public function testItReflectsGlobalConstantInNamespaceAsUnresolvedConstantType(): void
    {
        $types = $this->reflect(
            '<?php namespace X; PHP_INT_MIN;',
            static fn(Node $node): \Generator => $node instanceof StmtExpr ? yield $node->expr : null,
        );

        self::assertEquals([new UnresolvedConstantType('X\PHP_INT_MIN', 'PHP_INT_MIN')], $types);
    }

    /**
     * @param \Closure(Node): \Generator<array-key, Expr> $expressionFinder
     * @return array<?Type>
     */
    private function reflect(string $code, \Closure $expressionFinder): array
    {
        self::$parser ??= (new ParserFactory())->createForHostVersion();
        $nodes = self::$parser->parse($code) ?? [];

        $nameResolver = new NameResolver();
        $contextVisitor = new ContextVisitor($code, 'file.php', $nameResolver->getNameContext());
        $findAndReflect = new FindAndReflectVisitor($contextVisitor, $expressionFinder);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($contextVisitor);
        $traverser->addVisitor($findAndReflect);
        $traverser->traverse($nodes);

        return $findAndReflect->types;
    }
}
