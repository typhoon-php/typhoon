<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\Metadata\TypeMetadata;
use Typhoon\Type\types;

final class EnumReflections
{
    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct() {}

    /**
     * @param class-string $class
     */
    public static function name(string $class): PropertyMetadata
    {
        return new PropertyMetadata(
            name: 'name',
            class: $class,
            modifiers: \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_READONLY,
            type: TypeMetadata::create(native: types::string, phpDoc: types::nonEmptyString),
        );
    }

    /**
     * @param class-string $class
     */
    public static function value(string $class, TypeMetadata $type): PropertyMetadata
    {
        return new PropertyMetadata(
            name: 'value',
            class: $class,
            modifiers: \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_READONLY,
            type: $type,
        );
    }

    /**
     * @param class-string $class
     */
    public static function cases(string $class): MethodMetadata
    {
        return new MethodMetadata(
            name: 'cases',
            class: $class,
            modifiers: \ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC,
            parameters: [],
            returnType: TypeMetadata::create(types::array(), types::list(types::object($class))),
            internal: true,
        );
    }

    /**
     * @param class-string $class
     */
    public static function from(string $class, TypeMetadata $valueType): MethodMetadata
    {
        return new MethodMetadata(
            name: 'from',
            class: $class,
            modifiers: \ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC,
            parameters: [
                new ParameterMetadata(
                    position: 0,
                    name: 'value',
                    class: $class,
                    functionOrMethod: 'from',
                    type: $valueType,
                ),
            ],
            returnType: TypeMetadata::create(types::array(), types::list(types::object($class))),
            internal: true,
            throwsType: types::object(\ValueError::class),
        );
    }

    /**
     * @param class-string $class
     */
    public static function tryFrom(string $class, TypeMetadata $valueType): MethodMetadata
    {
        return new MethodMetadata(
            name: 'tryFrom',
            class: $class,
            modifiers: \ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC,
            parameters: [
                new ParameterMetadata(
                    position: 0,
                    name: 'value',
                    class: $class,
                    functionOrMethod: 'tryFrom',
                    type: $valueType,
                ),
            ],
            returnType: TypeMetadata::create(
                types::nullable(types::array()),
                types::nullable(types::list(types::object($class))),
            ),
            internal: true,
        );
    }
}
