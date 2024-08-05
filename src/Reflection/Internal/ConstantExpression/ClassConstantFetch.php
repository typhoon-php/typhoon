<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<mixed>
 */
final class ClassConstantFetch implements Expression
{
    public function __construct(
        public readonly Expression $class,
        private readonly Expression $name,
    ) {}

    /**
     * @return non-empty-string
     */
    public function evaluateClass(?TyphoonReflector $reflector = null): string
    {
        $class = $this->class->evaluate($reflector);
        \assert(\is_string($class) && $class !== '');

        return $class;
    }

    /**
     * @return non-empty-string
     */
    public function evaluateName(?TyphoonReflector $reflector = null): string
    {
        $name = $this->name->evaluate($reflector);
        \assert(\is_string($name) && $name !== '');

        return $name;
    }

    public function recompile(CompilationContext $context): Expression
    {
        return new self(
            class: $this->class->recompile($context),
            name: $this->name->recompile($context),
        );
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        $class = $this->evaluateClass($reflector);
        $name = $this->evaluateName($reflector);

        if ($name === 'class') {
            return $class;
        }

        if ($reflector === null) {
            return \constant($class . '::' . $name);
        }

        return $reflector->reflectClass($class)->constants()[$name]->evaluate();
    }
}
