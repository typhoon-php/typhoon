<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\ConstantExpression;

use Typhoon\Reflection\Exception\DeclarationNotFound;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements Expression<mixed>
 */
final class ConstantFetch implements Expression
{
    /**
     * @param non-empty-string $namespacedName
     * @param ?non-empty-string $globalName
     */
    public function __construct(
        private readonly string $namespacedName,
        private readonly ?string $globalName = null,
    ) {}

    /**
     * @return non-empty-string
     */
    public function name(?TyphoonReflector $reflector = null): string
    {
        if (\defined($this->namespacedName)) {
            return $this->namespacedName;
        }

        if ($reflector !== null) {
            try {
                return $reflector->reflectConstant($this->namespacedName)->id->name;
            } catch (DeclarationNotFound) {
            }
        }

        if ($this->globalName === null) {
            throw new \LogicException(\sprintf('Constant %s is not defined', $this->namespacedName));
        }

        if (\defined($this->globalName)) {
            return $this->globalName;
        }

        if ($reflector !== null) {
            try {
                return $reflector->reflectConstant($this->globalName)->id->name;
            } catch (DeclarationNotFound) {
            }
        }

        throw new \LogicException(\sprintf('Constants %s and %s are not defined', $this->namespacedName, $this->globalName));
    }

    public function recompile(CompilationContext $context): Expression
    {
        return $this;
    }

    public function evaluate(?TyphoonReflector $reflector = null): mixed
    {
        if (\defined($this->namespacedName)) {
            return \constant($this->namespacedName);
        }

        if ($reflector !== null) {
            try {
                return $reflector->reflectConstant($this->namespacedName);
            } catch (DeclarationNotFound) {
            }
        }

        if ($this->globalName === null) {
            throw new \LogicException(\sprintf('Constant %s is not defined', $this->namespacedName));
        }

        if (\defined($this->globalName)) {
            return \constant($this->globalName);
        }

        if ($reflector !== null) {
            try {
                return $reflector->reflectConstant($this->globalName);
            } catch (DeclarationNotFound) {
            }
        }

        throw new \LogicException(\sprintf('Constants %s and %s are not defined', $this->namespacedName, $this->globalName));
    }
}
