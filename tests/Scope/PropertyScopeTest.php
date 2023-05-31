<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\types;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PropertyScope::class)]
final class PropertyScopeTest extends TestCase
{
    public function testItResolvesClassThroughClassScopeIfConfigured(): void
    {
        $scope = new PropertyScope(new ClassLikeScope(self::class));

        $class = $scope->resolveClass(new Name(Scope::SELF));

        self::assertSame(self::class, $class);
    }

    public function testItResolvesClassTemplateIfNonStatic(): void
    {
        $scope = new PropertyScope(new ClassLikeScope(self::class, templateNames: ['T1']));

        $template = $scope->tryResolveTemplate('T1');

        self::assertEquals(types::classTemplate('T1', self::class), $template);
    }

    public function testItDoesNotResolveClassTemplateIfNonStatic(): void
    {
        $scope = new PropertyScope(
            new ClassLikeScope(self::class, templateNames: ['T1']),
            static: true,
        );

        $template = $scope->tryResolveTemplate('T1');

        self::assertNull($template);
    }
}
