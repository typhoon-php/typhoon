<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use Typhoon\Reflection\TemplateReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 * @psalm-suppress PossiblyUnusedProperty
 */
final class MethodMetadata
{
    /**
     * @var ?array{class-string, non-empty-string}
     */
    public ?array $prototype = null;

    /**
     * @param non-empty-string $name
     * @param class-string $class
     * @param list<TemplateReflection> $templates
     * @param list<ParameterMetadata> $parameters
     * @param non-empty-string|false $docComment
     * @param non-empty-string|false $extension
     * @param non-empty-string|false $file
     * @param positive-int|false $startLine
     * @param positive-int|false $endLine
     * @param int-mask-of<\ReflectionMethod::IS_*> $modifiers
     * @param list<AttributeMetadata> $attributes
     */
    public function __construct(
        public readonly string $name,
        public string $class,
        public readonly int $modifiers,
        public array $parameters,
        public TypeMetadata $returnType,
        public readonly array $templates = [],
        public readonly string|false $docComment = false,
        public readonly bool $internal = false,
        public readonly string|false $extension = false,
        public readonly string|false $file = false,
        public readonly int|false $startLine = false,
        public readonly int|false $endLine = false,
        public readonly bool $returnsReference = false,
        public readonly bool $generator = false,
        public readonly bool $deprecated = false,
        public readonly array $attributes = [],
    ) {}

    /**
     * @param class-string $class
     */
    public function withClass(string $class): self
    {
        $metadata = clone $this;
        $metadata->class = $class;

        return $metadata;
    }

    /**
     * @param array{class-string, non-empty-string} $prototype
     */
    public function withPrototype(array $prototype): self
    {
        $metadata = clone $this;
        $metadata->prototype = $prototype;

        return $metadata;
    }

    /**
     * @param array<non-empty-string, TypeMetadata> $parameterTypes
     */
    public function withTypes(array $parameterTypes, TypeMetadata $returnType): self
    {
        $method = clone $this;
        $parametersByName = array_column($method->parameters, null, 'name');

        foreach ($parameterTypes as $name => $parameterType) {
            $parametersByName[$name] = $parametersByName[$name]->withType($parameterType);
        }

        $method->parameters = array_values($parametersByName);
        $method->returnType = $returnType;

        return $method;
    }
}
