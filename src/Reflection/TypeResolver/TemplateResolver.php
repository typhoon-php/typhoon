<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Reflection\TemplateReflection;
use Typhoon\Type\AtClass;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TemplateResolver extends RecursiveTypeReplacer
{
    /**
     * @param array<non-empty-string, Type> $templateArguments
     * @param ?non-empty-string $self
     * @param ?non-empty-string $parent
     */
    public function __construct(
        private readonly array $templateArguments = [],
        private readonly ?string $self = null,
        private readonly ?string $parent = null,
        private readonly bool $resolveStatic = false,
    ) {}

    /**
     * @param array<TemplateReflection> $templates
     * @param array<Type> $arguments
     * @return array<non-empty-string, Type>
     */
    public static function prepareTemplateArguments(array $templates, array $arguments): array
    {
        $resolvedArguments = [];

        foreach ($templates as $template) {
            $resolvedArguments[$template->name] = $arguments[$template->name]
                ?? $arguments[$template->getPosition()]
                ?? $template->getConstraint();
        }

        return $resolvedArguments;
    }

    public function template(Type $self, string $name, AtClass|AtFunction|AtMethod $declaredAt, array $arguments): mixed
    {
        if ($name === 'self') {
            if ($this->self === null) {
                return $self;
            }

            return types::object($this->self, ...$this->resolveArguments($arguments));
        }

        if ($name === 'parent') {
            if ($this->parent === null) {
                return $self;
            }

            return types::object($this->parent, ...$this->resolveArguments($arguments));
        }

        if ($name === 'static') {
            if ($this->resolveStatic) {
                if ($this->self !== null) {
                    return types::object($this->self, ...$this->resolveArguments($arguments));
                }

                \assert($declaredAt instanceof AtClass, 'static template type is expected to be declared at class, got ' . $declaredAt::class);

                return types::object($declaredAt->name, ...$this->resolveArguments($arguments));
            }

            if ($this->self === null) {
                return $self;
            }

            return types::template($name, types::atClass($this->self), ...$arguments);
        }

        return $this->templateArguments[$name] ?? $self;
    }

    /**
     * @param list<Type> $arguments
     * @return list<Type>
     */
    private function resolveArguments(array $arguments): array
    {
        return array_map(fn(Type $argument): Type => $argument->accept($this), $arguments);
    }
}
