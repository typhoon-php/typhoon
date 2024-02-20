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
 * @psalm-import-type TraitMethodAliases from ClassMetadata
 * @psalm-import-type TraitMethodPrecedence from ClassMetadata
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
     * @param list<MethodMetadata> $methods
     */
    public function setOwn(array $methods): void
    {
        foreach ($methods as $method) {
            $this->method($method->name)->setOwn($method);
        }
    }

    /**
     * @param list<NamedObjectType> $types
     * @param TraitMethodAliases $traitMethodAliases
     * @param TraitMethodPrecedence $traitMethodPrecedence
     */
    public function addUsed(array $types, array $traitMethodAliases, array $traitMethodPrecedence): void
    {
        foreach (array_column($types, null, 'class') as $type) {
            $trait = ($this->classMetadataReflector)($type->class);
            $templateResolver = TemplateResolver::create($trait->templates, $type->templateArguments);

            foreach ($trait->resolvedMethods($this->classMetadataReflector) as $method) {
                $name = $method->name;

                if (isset($traitMethodPrecedence[$name]) && $traitMethodPrecedence[$name] !== $trait->name) {
                    continue;
                }

                foreach ($traitMethodAliases[$trait->name][$name] ?? [] as $alias) {
                    $this->method($alias->alias ?? $name)->addUsed($method->toAlias($alias), $templateResolver);
                }

                $this->method($name)->addUsed($method, $templateResolver);
            }
        }
    }

    /**
     * @param list<NamedObjectType> $types
     */
    public function addInherited(array $types): void
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
