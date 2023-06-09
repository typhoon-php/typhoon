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
            classScope: new ClassLikeScope(self::class, templateNames: ['T']),
            name: 'method',
            templateNames: ['T'],
        );

        $template = $scope->tryResolveTemplate('T');

        self::assertEquals(types::methodTemplate(self::class, 'method', 'T'), $template);
    }

    public function testItResolvesClassTemplateIfNonStatic(): void
    {
        $scope = new MethodScope(
            classScope: new ClassLikeScope(self::class, templateNames: ['T']),
            name: 'method',
        );

        $template = $scope->tryResolveTemplate('T');

        self::assertEquals(types::classTemplate(self::class, 'T'), $template);
    }

    public function testItDoesNotResolveClassTemplateIfNonStatic(): void
    {
        $scope = new MethodScope(
            classScope: new ClassLikeScope(self::class, templateNames: ['T']),
            name: 'method',
            static: true,
        );

        $template = $scope->tryResolveTemplate('T');

        self::assertNull($template);
    }
}
