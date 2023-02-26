<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Reflection;

use PHP\ExtendedTypeSystem\Type\Type;

/**
 * @psalm-api
 */
interface TypeReflector
{
    /**
     * @param ?class-string $scopeClass
     */
    public function reflectTypeFromString(string $type, ?string $scopeClass = null): ?Type;

    /**
     * @param callable-string|\Closure $function
     */
    public function reflectFunctionParameterType(string|\Closure $function, string $parameter): ?Type;

    /**
     * @param callable-string|\Closure $function
     */
    public function reflectFunctionReturnType(string|\Closure $function): ?Type;

    /**
     * @param class-string $class
     */
    public function reflectPropertyType(string $class, string $property): ?Type;

    /**
     * @param class-string $class
     */
    public function reflectMethodParameterType(string $class, string $method, string $parameter): ?Type;

    /**
     * @param class-string $class
     */
    public function reflectMethodReturnType(string $class, string $method): ?Type;
}
