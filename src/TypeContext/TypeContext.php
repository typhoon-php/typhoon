<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeContext;

use Typhoon\Reflection\NameContext\NameContext;
use Typhoon\Reflection\NameContext\NameResolver;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-type Types = array<non-empty-string, \Closure(list<Type>): Type>
 */
final class TypeContext implements NameResolver
{
    /**
     * @var Types
     */
    private array $aliasTypes = [];

    /**
     * @var Types
     */
    private array $classTemplateTypes = [];

    /**
     * @var Types
     */
    private array $methodTemplateTypes = [];

    public function __construct(
        private NameResolver $nameResolver = new NameContext(),
        private readonly ClassExistenceChecker $classExistenceChecker = new RuntimeExistenceChecker(),
        private readonly ConstantExistenceChecker $constantExistenceChecker = new RuntimeExistenceChecker(),
    ) {}

    /**
     * @param non-empty-string $name
     * @param Types $aliasTypes
     * @param Types $templateTypes
     */
    public function enterClass(string $name, bool $trait, array $aliasTypes = [], array $templateTypes = []): void
    {
        $this->leaveClass();

        $this->aliasTypes = $aliasTypes;
        $this->classTemplateTypes = $templateTypes;
        $this->classTemplateTypes['static'] = /** @param list<Type> $arguments */ static fn(array $arguments): Type => types::template('static', types::atClass($name), ...$arguments);

        if ($trait) {
            $this->classTemplateTypes['self'] = /** @param list<Type> $arguments */ static fn(array $arguments): Type => types::template('self', types::atClass($name), ...$arguments);
            $this->classTemplateTypes['parent'] = /** @param list<Type> $arguments */ static fn(array $arguments): Type => types::template('parent', types::atClass($name), ...$arguments);
        }
    }

    /**
     * @param Types $templateTypes
     */
    public function enterMethod(array $templateTypes = []): void
    {
        $this->leaveMethod();

        $this->methodTemplateTypes = $templateTypes;
    }

    public function leaveMethod(): void
    {
        $this->methodTemplateTypes = [];
    }

    public function leaveClass(): void
    {
        $this->leaveMethod();

        $this->aliasTypes = [];
        $this->classTemplateTypes = [];
    }

    public function resolveNameAsClass(string $name): string
    {
        return $this->nameResolver->resolveNameAsClass($name);
    }

    public function resolveNameAsConstant(string $name): array
    {
        return $this->nameResolver->resolveNameAsConstant($name);
    }

    /**
     * @param list<Type> $templateArguments
     */
    public function resolveNameAsType(string $name, array $templateArguments = [], bool $classOnly = false): Type
    {
        if (isset($this->aliasTypes[$name])) {
            return ($this->aliasTypes[$name])($templateArguments);
        }

        if (isset($this->classTemplateTypes[$name])) {
            return ($this->classTemplateTypes[$name])($templateArguments);
        }

        if (isset($this->methodTemplateTypes[$name])) {
            return ($this->methodTemplateTypes[$name])($templateArguments);
        }

        $class = $this->resolveNameAsClass($name);

        if ($classOnly || !$this->isNameConstantLike($class) || $this->classExistenceChecker->classExists($class)) {
            return types::object($class, ...$templateArguments);
        }

        $constants = $this->resolveNameAsConstant($name);

        if (!isset($constants[1]) || $this->constantExistenceChecker->constantExists($constants[0])) {
            return types::constant($constants[0]);
        }

        return types::constant($constants[1]);
    }

    public function __clone()
    {
        $this->nameResolver = clone $this->nameResolver;
    }

    private function isNameConstantLike(string $name): bool
    {
        return preg_match('/\\\?[A-Z_\x80-\xff]+$/', $name) === 1;
    }
}
