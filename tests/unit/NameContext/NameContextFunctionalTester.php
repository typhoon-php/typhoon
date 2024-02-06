<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertSame;

final class NameContextFunctionalTester extends NodeVisitorAbstract
{
    private static ?self $instance = null;

    /**
     * @var list<mixed>
     */
    private array $expectedValues = [];

    private function __construct(
        private readonly string $file,
        private readonly NameContext $nameContext,
    ) {}

    public static function record(mixed $value): void
    {
        \assert(self::$instance !== null);
        self::$instance->expectedValues[] = $value;
    }

    public static function test(string $file): void
    {
        require_once __DIR__ . '/functions.php';

        $nameContext = new NameContext();
        $visitor = new self($file, $nameContext);

        try {
            self::$instance = $visitor;
            /** @psalm-suppress UnresolvableInclude */
            include $file;
        } finally {
            self::$instance = null;
        }

        $phpParser = (new ParserFactory())->createForHostVersion();
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameContextVisitor($nameContext));
        $traverser->addVisitor($visitor);
        $traverser->traverse($phpParser->parse(file_get_contents($file)) ?? throw new \LogicException());

        assertEmpty($visitor->expectedValues);
    }

    public function enterNode(Node $node): ?int
    {
        if (!$node instanceof Node\Expr\FuncCall) {
            return null;
        }

        if (!$node->name instanceof Node\Name) {
            return null;
        }

        if ($node->name->toString() !== 'check') {
            return null;
        }

        \assert(\count($node->args) === 1);
        \assert($node->args[0] instanceof Node\Arg);

        $value = $node->args[0]->value;

        if ($value instanceof Node\Expr\ClassConstFetch) {
            \assert($value->class instanceof Node\Name);

            assertSame(
                array_shift($this->expectedValues),
                $this->nameContext->resolveNameAsClass($value->class->toCodeString()),
                sprintf('Failed to resolve class name at %s:%d.', $this->file, $node->getLine()),
            );

            return null;
        }

        if ($value instanceof Node\Expr\ConstFetch) {
            $constants = $this->nameContext->resolveNameAsConstant($value->name->toCodeString());

            foreach ($constants as $constant) {
                if (!\defined($constant)) {
                    continue;
                }

                assertSame(
                    array_shift($this->expectedValues),
                    \constant($constant),
                    sprintf('Failed to resolve constant name at %s:%d.', $this->file, $node->getLine()),
                );

                return null;
            }

            return null;
        }

        return null;
    }
}
