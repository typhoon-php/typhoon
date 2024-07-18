<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\DeclarationId\Id;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<mixed>
 */
final class ClassConstantFetch implements Expression
{
    public function __construct(
        private readonly Expression $class,
        private readonly Expression $name,
    ) {}

    /**
     * @return non-empty-string
     */
    public function class(?TyphoonReflector $reflector = null): string
    {
        $class = $this->class->evaluate($reflector);
        \assert(\is_string($class) && $class !== '');

        return $class;
    }

    /**
     * @return non-empty-string
     */
    public function name(?TyphoonReflector $reflector = null): string
    {
        $name = $this->name->evaluate($reflector);
        \assert(\is_string($name) && $name !== '');

        return $name;
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        $class = $this->class($reflector);
        $name = $this->name($reflector);

        if ($name === 'class') {
            return $class;
        }

        if ($reflector === null) {
            return \constant($class . '::' . $name);
        }

        return $reflector->reflect(Id::namedClass($class))->consts()[$name]->value();
    }
}
