<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeContext;

use Typhoon\Reflection\NameContext\NameContext;
use Typhoon\Reflection\NameContext\NameResolver;
use Typhoon\Reflection\NameContext\UnqualifiedName;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Type\TemplateType;
use Typhoon\Type\Type;
use Typhoon\Type\types;

final class TypeContext implements NameResolver
{
    /**
     * @var array<non-empty-string, TemplateType|callable(): TemplateType>
     */
    private array $templateTypes = [];

    public function __construct(
        private NameResolver $nameResolver = new NameContext(),
        private readonly ClassExistenceChecker $classExistenceChecker = new RuntimeExistenceChecker(),
        private readonly ConstantExistenceChecker $constantExistenceChecker = new RuntimeExistenceChecker(),
    ) {}

    /**
     * @template TReturn
     * @param array<non-empty-string, TemplateType|callable(): TemplateType> $templateTypes
     * @param \Closure(): TReturn $do
     * @return TReturn
     */
    public function inContextOfTemplates(array $templateTypes, \Closure $do): mixed
    {
        try {
            $this->addTemplates($templateTypes);

            return $do();
        } finally {
            $this->removeTemplates(array_keys($templateTypes));
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
        if (isset($this->templateTypes[$name])) {
            /** @var non-empty-string $name */
            $templateType = $this->templateTypes[$name];

            if ($templateType instanceof TemplateType) {
                return $templateType;
            }

            return $this->templateTypes[$name] = $templateType();
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
     * @param array<non-empty-string, TemplateType|callable(): TemplateType> $templateTypes
     */
    private function addTemplates(array $templateTypes): void
    {
        foreach ($templateTypes as $name => $templateType) {
            if (isset($this->templateTypes[$name])) {
                throw new ReflectionException();
            }

            $this->templateTypes[(new UnqualifiedName($name))->toString()] = $templateType;
        }
    }

    /**
     * @param array<non-empty-string> $names
     */
    private function removeTemplates(array $names): void
    {
        foreach ($names as $name) {
            unset($this->templateTypes[$name]);
        }
    }

    private function isNameConstantLike(string $name): bool
    {
        return preg_match('/\\\?[A-Z_\x80-\xff]+$/', $name) === 1;
    }
}
