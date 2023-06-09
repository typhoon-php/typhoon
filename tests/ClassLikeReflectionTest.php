<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use ExtendedTypeSystem\TypeVisitor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassLikeReflection::class)]
final class ClassLikeReflectionTest extends TestCase
{
    public function testItReturnsTemplate(): void
    {
        $template = new TemplateReflection(0, 'T');
        $reflection = ClassLikeReflection::create(\stdClass::class, templates: [$template]);

        $returnedTemplate = $reflection->template('T');

        self::assertSame($template, $returnedTemplate);
    }

    public function testItThrowsIfTemplateDoesNotExist(): void
    {
        $reflection = ClassLikeReflection::create(\stdClass::class);

        $this->expectExceptionObject(new TypeReflectionException('Class stdClass does not have template T.'));

        $reflection->template('T');
    }

    public function testItReturnsPropertyType(): void
    {
        $propertyType = new TypeReflection(types::float);
        $reflection = ClassLikeReflection::create(\stdClass::class, propertyTypes: ['prop' => $propertyType]);

        $returnedPropertyType = $reflection->propertyType('prop');

        self::assertSame($propertyType, $returnedPropertyType);
    }

    public function testItThrowsIfPropertyDoesNotExist(): void
    {
        $reflection = ClassLikeReflection::create(\stdClass::class);

        $this->expectExceptionObject(new TypeReflectionException('Property stdClass::$prop does not exist.'));

        $reflection->propertyType('prop');
    }

    public function testItReturnsMethod(): void
    {
        $method = MethodReflection::create(\stdClass::class, 'method');
        $reflection = ClassLikeReflection::create(\stdClass::class, methods: [$method]);

        $returnedMethod = $reflection->method('method');

        self::assertSame($method, $returnedMethod);
    }

    public function testItThrowsIfMethodDoesNotExist(): void
    {
        $reflection = ClassLikeReflection::create(\stdClass::class);

        $this->expectExceptionObject(new TypeReflectionException('Method stdClass::method() does not exist.'));

        $reflection->method('method');
    }

    public function testItResolvesStatic(): void
    {
        $static = types::static(\stdClass::class);
        $reflection = ClassLikeReflection::create(
            name: \stdClass::class,
            propertyTypes: ['a' => new TypeReflection($static)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection($static)],
                    returnType: new TypeReflection($static),
                ),
            ],
        );
        $resolvedStatic = types::object(\stdClass::class);
        $expectedReflection = ClassLikeReflection::create(
            name: \stdClass::class,
            propertyTypes: ['a' => new TypeReflection($resolvedStatic)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection($resolvedStatic)],
                    returnType: new TypeReflection($resolvedStatic),
                ),
            ],
        );

        $reflectionWithResolvedStatic = $reflection->withResolvedStatic();

        self::assertEquals($expectedReflection, $reflectionWithResolvedStatic);
    }

    public function testItResolvesWithSameInstanceIfNoTemplates(): void
    {
        $reflection = ClassLikeReflection::create(\stdClass::class);

        $reflectionWithResolvedStatic = $reflection->withResolvedTemplates(['T' => types::float]);

        self::assertSame($reflection, $reflectionWithResolvedStatic);
    }

    public function testItResolvesTemplates(): void
    {
        $classTemplate = types::classTemplate(\stdClass::class, 'T');
        $reflection = ClassLikeReflection::create(
            name: \stdClass::class,
            templates: [new TemplateReflection(0, 'T')],
            propertyTypes: ['a' => new TypeReflection($classTemplate)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection($classTemplate)],
                    returnType: new TypeReflection($classTemplate),
                ),
            ],
        );
        $expectedReflection = ClassLikeReflection::create(
            name: \stdClass::class,
            templates: [new TemplateReflection(0, 'T')],
            propertyTypes: ['a' => new TypeReflection(types::float)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection(types::float)],
                    returnType: new TypeReflection(types::float),
                ),
            ],
        );

        $reflectionWithResolvedStatic = $reflection->withResolvedTemplates(['T' => types::float]);

        self::assertEquals($expectedReflection, $reflectionWithResolvedStatic);
    }

    public function testItResolvesTemplatesByPosition(): void
    {
        $classTemplate = types::classTemplate(\stdClass::class, 'T');
        $reflection = ClassLikeReflection::create(
            name: \stdClass::class,
            templates: [new TemplateReflection(2, 'T')],
            propertyTypes: ['a' => new TypeReflection($classTemplate)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection($classTemplate)],
                    returnType: new TypeReflection($classTemplate),
                ),
            ],
        );
        $expectedReflection = ClassLikeReflection::create(
            name: \stdClass::class,
            templates: [new TemplateReflection(2, 'T')],
            propertyTypes: ['a' => new TypeReflection(types::float)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection(types::float)],
                    returnType: new TypeReflection(types::float),
                ),
            ],
        );

        $reflectionWithResolvedStatic = $reflection->withResolvedTemplates([2 => types::float]);

        self::assertEquals($expectedReflection, $reflectionWithResolvedStatic);
    }

    public function testItResolvesTemplatesWithConstraint(): void
    {
        $classTemplate = types::classTemplate(\stdClass::class, 'T');
        $reflection = ClassLikeReflection::create(
            name: \stdClass::class,
            templates: [new TemplateReflection(2, 'T', types::bool)],
            propertyTypes: ['a' => new TypeReflection($classTemplate)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection($classTemplate)],
                    returnType: new TypeReflection($classTemplate),
                ),
            ],
        );
        $expectedReflection = ClassLikeReflection::create(
            name: \stdClass::class,
            templates: [new TemplateReflection(2, 'T', types::bool)],
            propertyTypes: ['a' => new TypeReflection(types::bool)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection(types::bool)],
                    returnType: new TypeReflection(types::bool),
                ),
            ],
        );

        $reflectionWithResolvedStatic = $reflection->withResolvedTemplates();

        self::assertEquals($expectedReflection, $reflectionWithResolvedStatic);
    }

    public function testItResolvesTypes(): void
    {
        $reflection = ClassLikeReflection::create(
            name: \stdClass::class,
            propertyTypes: ['a' => new TypeReflection()],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection()],
                    returnType: new TypeReflection(),
                ),
            ],
        );
        $expectedReflection = ClassLikeReflection::create(
            name: \stdClass::class,
            propertyTypes: ['a' => new TypeReflection(types::float)],
            methods: [
                MethodReflection::create(
                    reflectedClass: \stdClass::class,
                    name: 'method',
                    parameterTypes: ['a' => new TypeReflection(types::float)],
                    returnType: new TypeReflection(types::float),
                ),
            ],
        );
        /** @var MockObject&TypeVisitor<Type> */
        $typeResolver = $this->createMock(TypeVisitor::class);
        $typeResolver->method(self::anything())->willReturn(types::float);

        $reflectionWithResolvedTypes = $reflection->withResolvedTypes($typeResolver);

        self::assertEquals($expectedReflection, $reflectionWithResolvedTypes);
    }
}
