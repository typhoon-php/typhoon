<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\TypeVisitor;

final class MethodReflection extends Reflection
{
    /**
     * @param non-empty-string $name
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param array<non-empty-string, ParameterReflection> $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $static,
        public readonly bool $final,
        public readonly bool $abstract,
        public readonly Visibility $visibility,
        public readonly array $templates,
        public readonly array $parameters,
        public readonly TypeReflection $returnType,
    ) {
    }

    protected function withResolvedTypes(TypeVisitor $typeResolver): static
    {
        $data = get_object_vars($this);
        $data['parameters'] = array_map(
            static fn (ParameterReflection $parameter): ParameterReflection => $parameter->withResolvedTypes($typeResolver),
            $this->parameters,
        );
        $data['returnType'] = $this->returnType->withResolvedTypes($typeResolver);

        return new self(...$data);
    }

    protected function toChildOf(Reflection $parent): static
    {
        $data = get_object_vars($this);

        $parentParametersByPosition = array_values($parent->parameters);
        $data['parameters'] = array_map(
            static fn (ParameterReflection $parameter): ParameterReflection => isset($parentParametersByPosition[$parameter->position])
                ? $parameter->toChildOf($parentParametersByPosition[$parameter->position])
                : $parameter,
            $this->parameters,
        );
        $data['returnType'] = $this->returnType->toChildOf($parent->returnType);

        return new self(...$data);
    }
}
