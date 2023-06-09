<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use ExtendedTypeSystem\TypeVisitor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MethodReflection::class)]
final class MethodReflectionTest extends TestCase
{
    public function testItReturnsTemplate(): void
    {
        $template = new TemplateReflection(0, 'T');
        $reflection = MethodReflection::create(\stdClass::class, 'method', templates: [$template]);

        $returnedTemplate = $reflection->template('T');

        self::assertSame($template, $returnedTemplate);
    }

    public function testItThrowsIfTemplateDoesNotExist(): void
    {
        $reflection = MethodReflection::create(\stdClass::class, 'method');

        $this->expectExceptionObject(new TypeReflectionException('Method stdClass::method() does not have template T.'));

        $reflection->template('T');
    }

    public function testItReturnsParameterType(): void
    {
        $parameterType = new TypeReflection(types::float);
        $reflection = MethodReflection::create(\stdClass::class, 'method', parameterTypes: ['param' => $parameterType]);

        $returnedParameterType = $reflection->parameterType('param');

        self::assertSame($parameterType, $returnedParameterType);
    }

    public function testItThrowsIfParameterDoesNotExist(): void
    {
        $reflection = MethodReflection::create(\stdClass::class, 'method');

        $this->expectExceptionObject(new TypeReflectionException('Method stdClass::method() does not have parameter $param.'));

        $reflection->parameterType('param');
    }

    public function testItResolvesTypes(): void
    {
        $reflection = MethodReflection::create(
            reflectedClass: \stdClass::class,
            name: 'method',
            parameterTypes: ['a' => new TypeReflection()],
            returnType: new TypeReflection(),
        );
        $expectedReflection = MethodReflection::create(
            reflectedClass: \stdClass::class,
            name: 'method',
            parameterTypes: ['a' => new TypeReflection(types::float)],
            returnType: new TypeReflection(types::float),
        );
        /** @var MockObject&TypeVisitor<Type> */
        $typeResolver = $this->createMock(TypeVisitor::class);
        $typeResolver->method(self::anything())->willReturn(types::float);

        $reflectionWithResolvedTypes = $reflection->withResolvedTypes($typeResolver);

        self::assertEquals($expectedReflection, $reflectionWithResolvedTypes);
    }
}
