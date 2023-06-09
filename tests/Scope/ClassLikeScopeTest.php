<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\Reflection\TypeReflectionException;
use ExtendedTypeSystem\types;
use PhpParser\ErrorHandler\Throwing;
use PhpParser\NameContext;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassLikeScope::class)]
final class ClassLikeScopeTest extends TestCase
{
    public function testItResolvesSelf(): void
    {
        $scope = new ClassLikeScope(self::class);

        $class = $scope->resolveClass(new Name(Scope::SELF));

        self::assertSame(self::class, $class);
    }

    public function testItResolvesParent(): void
    {
        $scope = new ClassLikeScope(self::class, parent: parent::class);

        $class = $scope->resolveClass(new Name(Scope::PARENT));

        self::assertSame(parent::class, $class);
    }

    public function testItThrowsIfNoParent(): void
    {
        $scope = new ClassLikeScope(\stdClass::class);

        $this->expectExceptionObject(new TypeReflectionException('Failed to resolve name "parent": class stdClass does not have a parent.'));

        $scope->resolveClass(new Name(Scope::PARENT));
    }

    public function testItResolvesClassThroughGlobalScopeByDefault(): void
    {
        $scope = new ClassLikeScope(self::class);

        $class = $scope->resolveClass(new Name('A'));

        self::assertSame('A', $class);
    }

    public function testItResolvesClassThroughParentScopeIfConfigured(): void
    {
        $nameContext = new NameContext(new Throwing());
        $nameContext->startNamespace(new Name('NS'));
        $scope = new ClassLikeScope(self::class, parentScope: new NameContextScope($nameContext));

        $class = $scope->resolveClass(new Name('A'));

        self::assertSame('NS\\A', $class);
    }

    public function testItResolvesTemplate(): void
    {
        $scope = new ClassLikeScope(self::class, templateNames: ['T']);

        $type = $scope->tryResolveTemplate('T');

        self::assertEquals(types::classTemplate(self::class, 'T'), $type);
    }

    public function testItResolvesTemplateAsNullIfTemplateDoesNotExist(): void
    {
        $scope = new ClassLikeScope(self::class, templateNames: ['T']);

        $type = $scope->tryResolveTemplate('T2');

        self::assertNull($type);
    }
}
