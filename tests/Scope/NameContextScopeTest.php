<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use PhpParser\ErrorHandler\Throwing;
use PhpParser\NameContext;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NameContextScope::class)]
final class NameContextScopeTest extends TestCase
{
    public function testItResolvesName(): void
    {
        $nameContext = new NameContext(new Throwing());
        $nameContext->startNamespace(new Name('NS'));
        $scope = new NameContextScope($nameContext);

        $class = $scope->resolveClass(new Name('A'));

        self::assertSame('NS\\A', $class);
    }

    public function testItDoesNotResolveTemplates(): void
    {
        $nameContext = new NameContext(new Throwing());
        $nameContext->addAlias(new Name('NS\\A'), 'T', Use_::TYPE_NORMAL);
        $scope = new NameContextScope($nameContext);

        $template = $scope->tryResolveTemplate('T');

        self::assertNull($template);
    }
}
