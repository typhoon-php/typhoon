<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\Reflection\TypeReflectionException;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GlobalScope::class)]
final class GlobalScopeTest extends TestCase
{
    public function testItResolvesClassAsIs(): void
    {
        $scope = new GlobalScope();

        $class = $scope->resolveClass(new Name('A\\B'));

        self::assertSame('A\\B', $class);
    }

    public function testItDoesNotResolveSelf(): void
    {
        $scope = new GlobalScope();

        $this->expectExceptionObject(new TypeReflectionException('Cannot resolve self in global scope.'));

        $scope->resolveClass(new Name(Scope::SELF));
    }

    public function testItDoesNotResolveParent(): void
    {
        $scope = new GlobalScope();

        $this->expectExceptionObject(new TypeReflectionException('Cannot resolve parent in global scope.'));

        $scope->resolveClass(new Name(Scope::PARENT));
    }

    public function testItDoesNotResolveTemplates(): void
    {
        $scope = new GlobalScope();

        $template = $scope->tryResolveTemplate('T');

        self::assertNull($template);
    }
}
