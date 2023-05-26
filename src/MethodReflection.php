<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @api
 * @psalm-immutable
 */
final class MethodReflection
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param non-empty-string $name
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param array<non-empty-string, TypeReflection> $parameterTypes
     */
    public function __construct(
        public readonly string $name,
        private readonly array $templates,
        private readonly array $parameterTypes,
        private readonly TypeReflection $returnType,
    ) {
    }

    /**
     * @return array<non-empty-string, TemplateReflection>
     */
    public function templates(): array
    {
        return $this->templates;
    }

    public function template(int|string $name): TemplateReflection
    {
        if (\is_string($name)) {
            return $this->templates[$name] ?? throw new \RuntimeException();
        }

        foreach ($this->templates as $template) {
            if ($template->index === $name) {
                return $template;
            }
        }

        throw new \RuntimeException();
    }

    public function returnType(): TypeReflection
    {
        return $this->returnType;
    }

    /**
     * @return array<non-empty-string, TypeReflection>
     */
    public function parameterTypes(): array
    {
        return $this->parameterTypes;
    }

    public function parameterType(string $name): TypeReflection
    {
        return $this->parameterTypes[$name] ?? throw new \RuntimeException();
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function resolveTypes(TypeVisitor $typeResolver): self
    {
        return new self(
            name: $this->name,
            templates: $this->templates,
            parameterTypes: array_map(
                static fn (TypeReflection $type): TypeReflection => $type->resolve($typeResolver),
                $this->parameterTypes,
            ),
            returnType: $this->returnType->resolve($typeResolver),
        );
    }
}
