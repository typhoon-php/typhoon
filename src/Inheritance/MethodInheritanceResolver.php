<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\TypeMetadata;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Inheritance
 */
final class MethodInheritanceResolver
{
    private ?MethodMetadata $method = null;

    private bool $own = false;

    /**
     * @var ?array{class-string, non-empty-string}
     */
    private ?array $prototype = null;

    /**
     * @var array<non-empty-string, TypeInheritanceResolver>
     */
    private array $parameterTypes = [];

    private TypeInheritanceResolver $returnType;

    public function __construct()
    {
        $this->returnType = new TypeInheritanceResolver();
    }

    public function setOwn(MethodMetadata $method): void
    {
        $this->method = $method;
        $this->own = true;

        foreach ($method->parameters as $parameter) {
            $this->parameterTypes[$parameter->name] = new TypeInheritanceResolver();
            $this->parameterTypes[$parameter->name]->setOwn($parameter->type);
        }

        $this->returnType->setOwn($method->returnType);
    }

    /**
     * @param TypeVisitor<Type> $templateResolver
     */
    public function addInherited(MethodMetadata $method, TypeVisitor $templateResolver): void
    {
        if ($method->modifiers & \ReflectionMethod::IS_PRIVATE) {
            return;
        }

        if ($this->method !== null) {
            if ($this->own) {
                $this->prototype ??= $method->prototype ?? [$method->class, $method->name];
            }

            $inheritedMethodParameters = array_column($method->parameters, null, 'name');

            foreach ($this->parameterTypes as $name => $parameter) {
                if (isset($inheritedMethodParameters[$name])) {
                    $parameter->addInherited($inheritedMethodParameters[$name]->type, $templateResolver);
                }
            }

            $this->returnType->addInherited($method->returnType, $templateResolver);

            return;
        }

        $this->method = $method;

        foreach ($method->parameters as $parameter) {
            $this->parameterTypes[$parameter->name] = new TypeInheritanceResolver();
            $this->parameterTypes[$parameter->name]->addInherited($parameter->type, $templateResolver);
        }

        $this->returnType->addInherited($method->returnType, $templateResolver);
    }

    public function resolve(): ?MethodMetadata
    {
        if ($this->method === null) {
            return null;
        }

        $method = $this->method->withTypes(
            array_map(
                static fn(TypeInheritanceResolver $resolver): TypeMetadata => $resolver->resolve(),
                $this->parameterTypes,
            ),
            $this->returnType->resolve(),
        );

        if ($this->prototype !== null) {
            return $method->withPrototype($this->prototype);
        }

        return $method;
    }
}
