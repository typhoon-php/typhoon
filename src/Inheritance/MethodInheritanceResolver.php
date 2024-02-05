<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\TypeReflection;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Inheritance
 */
final class MethodInheritanceResolver
{
    private ?MethodReflection $method = null;

    /**
     * @var array<non-empty-string, TypeInheritanceResolver>
     */
    private array $parameterTypes = [];

    private TypeInheritanceResolver $returnType;

    public function __construct()
    {
        $this->returnType = new TypeInheritanceResolver();
    }

    public function setOwn(MethodReflection $method): void
    {
        $this->method = $method;

        foreach ($method->getParameters() as $parameter) {
            $this->parameterTypes[$parameter->name] = new TypeInheritanceResolver();
            $this->parameterTypes[$parameter->name]->setOwn($parameter->getType());
        }

        $this->returnType->setOwn($method->getReturnType());
    }

    /**
     * @param TypeVisitor<Type> $templateResolver
     */
    public function addInherited(MethodReflection $method, TypeVisitor $templateResolver): void
    {
        if ($this->method !== null) {
            foreach ($this->parameterTypes as $name => $parameter) {
                if ($method->hasParameterWithName($name)) {
                    $parameter->addInherited($method->getParameterByName($name)->getType(), $templateResolver);
                }
            }

            $this->returnType->addInherited($method->getReturnType(), $templateResolver);

            return;
        }

        $this->method = $method;

        foreach ($method->getParameters() as $parameter) {
            $this->parameterTypes[$parameter->name] = new TypeInheritanceResolver();
            $this->parameterTypes[$parameter->name]->addInherited($parameter->getType(), $templateResolver);
        }

        $this->returnType->addInherited($method->getReturnType(), $templateResolver);
    }

    public function resolve(): MethodReflection
    {
        \assert($this->method !== null);

        return $this->method->withTypes(
            array_map(
                static fn(TypeInheritanceResolver $resolver): TypeReflection => $resolver->resolve(),
                $this->parameterTypes,
            ),
            $this->returnType->resolve(),
        );
    }
}
