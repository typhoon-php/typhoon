<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\types;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MethodScope::class)]
final class MethodScopeTest extends TestCase
{
    public function testItResolvesClassThroughClassScopeIfConfigured(): void
    {
        $scope = new MethodScope(
            classScope: new ClassLikeScope(self::class),
            name: 'method',
        );

        $class = $scope->resolveClass(new Name(Scope::SELF));

        self::assertSame(self::class, $class);
    }

    public function testItResolvesMethodTemplateFirst(): void
    {
        $scope = new MethodScope(
            classScope: new ClassLikeScope(self::class, templateNames: ['T1']),
            name: 'method',
            templateNames: ['T1'],
        );

        $template = $scope->tryResolveTemplate('T1');

        self::assertEquals(types::methodTemplate('T1', self::class, 'method'), $template);
    }

    public function testItResolvesClassTemplateIfNonStatic(): void
    {
        $scope = new MethodScope(
            classScope: new ClassLikeScope(self::class, templateNames: ['T1']),
            name: 'method',
        );

        $template = $scope->tryResolveTemplate('T1');

        self::assertEquals(types::classTemplate('T1', self::class), $template);
    }

    public function testItDoesNotResolveClassTemplateIfNonStatic(): void
    {
        $scope = new MethodScope(
            classScope: new ClassLikeScope(self::class, templateNames: ['T1']),
            name: 'method',
            static: true,
        );

        $template = $scope->tryResolveTemplate('T1');

        self::assertNull($template);
    }
}
