<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\TypeResolver\TemplateTypeResolver;
use Typhoon\Type\NamedObjectType;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class MethodsInheritanceResolver
{
    /**
     * @var array<non-empty-string, MethodInheritanceResolver>
     */
    private array $methods = [];

    public function __construct(
        private readonly ClassReflector $classReflector,
    ) {}

    /**
     * @param iterable<MethodReflection> $methods
     */
    public function setOwn(iterable $methods): void
    {
        foreach ($methods as $method) {
            $this->method($method->name)->setOwn($method);
        }
    }

    public function addInherited(NamedObjectType ...$types): void
    {
        foreach ($types as $type) {
            $class = $this->classReflector->reflectClass($type->class);
            $templateResolver = TemplateTypeResolver::create($class->getTemplates(), $type->templateArguments);

            foreach ($class->getMethods() as $method) {
                $this->method($method->name)->addInherited($method, $templateResolver);
            }
        }
    }

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    public function resolve(): array
    {
        return array_map(
            static fn(MethodInheritanceResolver $resolver): MethodReflection => $resolver->resolve(),
            $this->methods,
        );
    }

    /**
     * @param non-empty-string $name
     */
    private function method(string $name): MethodInheritanceResolver
    {
        return $this->methods[$name] ??= new MethodInheritanceResolver();
    }
}
