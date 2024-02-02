<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeContext;

use Typhoon\Reflection\NameContext\NameContext;
use Typhoon\Reflection\NameContext\NameResolver;
use Typhoon\Reflection\NameContext\UnqualifiedName;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Type\Type;
use Typhoon\Type\types;

final class TypeContext implements NameResolver
{
    /**
     * @var array<non-empty-string, Type|callable(): Type>
     */
    private array $types = [];

    public function __construct(
        private NameResolver $nameResolver = new NameContext(),
        private readonly ClassExistenceChecker $classExistenceChecker = new RuntimeExistenceChecker(),
        private readonly ConstantExistenceChecker $constantExistenceChecker = new RuntimeExistenceChecker(),
    ) {}

    /**
     * @template TReturn
     * @param \Closure(): TReturn $action
     * @param array<non-empty-string, Type|callable(): Type> $types
     * @return TReturn
     */
    public function executeWithTypes(\Closure $action, array $types = []): mixed
    {
        try {
            $this->addTypes($types);

            return $action();
        } finally {
            $this->removeTypes(array_keys($types));
        }
    }

    public function resolveNameAsClass(string $name): string
    {
        return $this->nameResolver->resolveNameAsClass($name);
    }

    public function resolveNameAsConstant(string $name): array
    {
        return $this->nameResolver->resolveNameAsConstant($name);
    }

    public function resolveNameAsType(string $name): Type
    {
        if (isset($this->types[$name])) {
            /** @var non-empty-string $name */
            $type = $this->types[$name];

            if ($type instanceof Type) {
                return $type;
            }

            return $this->types[$name] = $type();
        }

        $class = $this->resolveNameAsClass($name);

        if (strtolower($class) === 'static') {
            return types::static($this->resolveNameAsClass('self'));
        }

        if (!$this->isNameConstantLike($class) || $this->classExistenceChecker->classExists($class)) {
            return types::object($class);
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

    /**
     * @param array<non-empty-string, Type|callable(): Type> $types
     */
    private function addTypes(array $types): void
    {
        foreach ($types as $name => $templateType) {
            if (isset($this->types[$name])) {
                throw new ReflectionException($name);
            }

            $this->types[(new UnqualifiedName($name))->toString()] = $templateType;
        }
    }

    /**
     * @param array<non-empty-string> $names
     */
    private function removeTypes(array $names): void
    {
        foreach ($names as $name) {
            unset($this->types[$name]);
        }
    }

    private function isNameConstantLike(string $name): bool
    {
        return preg_match('/\\\?[A-Z_\x80-\xff]+$/', $name) === 1;
    }
}
