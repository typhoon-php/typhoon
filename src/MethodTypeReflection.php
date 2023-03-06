<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class MethodTypeReflection
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param class-string $class
     * @param non-empty-string $name
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param array<non-empty-string, Type> $parameterTypes
     */
    public function __construct(
        public readonly string $class,
        public readonly string $name,
        public readonly array $templates,
        private readonly array $parameterTypes,
        public readonly Type $returnType,
    ) {
    }

    public function parameterType(string $name): Type
    {
        return $this->parameterTypes[$name] ?? throw new \LogicException(sprintf(
            'Parameter $%s is not defined in method %s::%s().',
            $name,
            $this->class,
            $this->name,
        ));
    }
}
