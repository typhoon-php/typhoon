<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\TypeResolver\TemplateResolver;
use Typhoon\Type\NamedObjectType;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-import-type ClassMetadataReflector from ClassMetadata
 */
final class MethodsInheritanceResolver
{
    /**
     * @var array<non-empty-string, MethodInheritanceResolver>
     */
    private array $methods = [];

    /**
     * @param class-string $class
     * @param ClassMetadataReflector $classMetadataReflector
     */
    public function __construct(
        private readonly string $class,
        private readonly \Closure $classMetadataReflector,
    ) {}

    /**
     * @param iterable<MethodMetadata> $methods
     */
    public function setOwn(iterable $methods): void
    {
        foreach ($methods as $method) {
            $this->method($method->name)->setOwn($method);
        }
    }

    public function addUsed(NamedObjectType ...$types): void
    {
        foreach ($types as $type) {
            $class = ($this->classMetadataReflector)($type->class);
            $templateResolver = TemplateResolver::create($class->templates, $type->templateArguments);

            foreach ($class->resolvedMethods($this->classMetadataReflector) as $method) {
                $this->method($method->name)->addUsed($method, $templateResolver);
            }
        }
    }

    public function addInherited(NamedObjectType ...$types): void
    {
        foreach ($types as $type) {
            $class = ($this->classMetadataReflector)($type->class);
            $templateResolver = TemplateResolver::create($class->templates, $type->templateArguments);

            foreach ($class->resolvedMethods($this->classMetadataReflector) as $method) {
                $this->method($method->name)->addInherited($method, $templateResolver);
            }
        }
    }

    /**
     * @return array<non-empty-string, MethodMetadata>
     */
    public function resolve(): array
    {
        return array_filter(
            array_map(
                static fn(MethodInheritanceResolver $resolver): ?MethodMetadata => $resolver->resolve(),
                $this->methods,
            ),
        );
    }

    /**
     * @param non-empty-string $name
     */
    private function method(string $name): MethodInheritanceResolver
    {
        return $this->methods[$name] ??= new MethodInheritanceResolver($this->class);
    }
}
