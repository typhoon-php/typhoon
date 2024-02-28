<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\Metadata\ClassConstantMetadata;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\InheritedName;
use Typhoon\Reflection\TypeResolver\TemplateResolver;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-import-type ClassMetadataReflector from ClassMetadata
 */
final class ClassConstantsInheritanceResolver
{
    /**
     * @var array<non-empty-string, ClassConstantInheritanceResolver>
     */
    private array $constants = [];

    /**
     * @param ClassMetadataReflector $classMetadataReflector
     * @param class-string $class
     * @param ?non-empty-string $parent
     */
    public function __construct(
        private readonly \Closure $classMetadataReflector,
        private readonly string $class,
        private readonly ?string $parent,
        private readonly bool $final,
    ) {}

    /**
     * @param iterable<ClassConstantMetadata> $constants
     */
    public function setOwn(iterable $constants): void
    {
        foreach ($constants as $constant) {
            $this->constant($constant->name)->setOwn($constant);
        }
    }

    public function addUsed(InheritedName ...$names): void
    {
        foreach ($names as $name) {
            $class = ($this->classMetadataReflector)($name->class);
            $templateResolver = TemplateResolver::create(
                templates: $class->templates,
                templateArguments: $name->templateArguments,
                self: $this->class,
                parent: $this->parent,
                resolveStatic: $this->final,
            );

            foreach ($class->resolvedConstants($this->classMetadataReflector) as $constant) {
                $this->constant($constant->name)->addUsed($constant, $templateResolver);
            }
        }
    }

    public function addInherited(InheritedName ...$names): void
    {
        foreach ($names as $name) {
            $class = ($this->classMetadataReflector)($name->class);
            $templateResolver = TemplateResolver::create(
                templates: $class->templates,
                templateArguments: $name->templateArguments,
                self: $this->class,
                parent: $this->parent,
                resolveStatic: $this->final,
            );

            foreach ($class->resolvedConstants($this->classMetadataReflector) as $constant) {
                $this->constant($constant->name)->addInherited($constant, $templateResolver);
            }
        }
    }

    /**
     * @return array<non-empty-string, ClassConstantMetadata>
     */
    public function resolve(): array
    {
        return array_filter(
            array_map(
                static fn(ClassConstantInheritanceResolver $resolver): ?ClassConstantMetadata => $resolver->resolve(),
                $this->constants,
            ),
        );
    }

    /**
     * @param non-empty-string $name
     */
    private function constant(string $name): ClassConstantInheritanceResolver
    {
        return $this->constants[$name] ??= new ClassConstantInheritanceResolver($this->class);
    }
}
