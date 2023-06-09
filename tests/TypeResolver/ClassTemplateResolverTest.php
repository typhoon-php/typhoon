<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeResolver;

use ExtendedTypeSystem\types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassTemplateResolver::class)]
final class ClassTemplateResolverTest extends TestCase
{
    public function testItResolvesClassTemplateType(): void
    {
        $type = types::list(types::classTemplate(\stdClass::class, 'T'));
        $expectedType = types::list(types::string);
        $resolver = new ClassTemplateResolver(\stdClass::class, ['T' => types::string]);

        $resolvedType = $type->accept($resolver);

        self::assertEquals($expectedType, $resolvedType);
    }

    public function testItDoesNotResolveIfClassDiffers(): void
    {
        $type = types::list(types::classTemplate(\stdClass::class, 'T'));
        $resolver = new ClassTemplateResolver(self::class, ['T' => types::string]);

        $resolvedType = $type->accept($resolver);

        self::assertEquals($type, $resolvedType);
    }

    public function testItDoesNotResolveIfTemplateDiffers(): void
    {
        $type = types::list(types::classTemplate(\stdClass::class, 'T'));
        $resolver = new ClassTemplateResolver(\stdClass::class, ['T2' => types::string]);

        $resolvedType = $type->accept($resolver);

        self::assertEquals($type, $resolvedType);
    }
}
