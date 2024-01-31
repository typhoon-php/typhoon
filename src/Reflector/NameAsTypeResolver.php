<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\NameResolution\NameResolver;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Type\TemplateType;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements NameResolver<Type>
 */
final class NameAsTypeResolver implements NameResolver
{
    /**
     * @var array<non-empty-string, TemplateReflector>
     */
    private readonly array $templateReflectorsByName;

    /**
     * @param array<TemplateReflector> $templateReflectors
     */
    public function __construct(
        private readonly ClassExistenceChecker $classExistenceChecker,
        array $templateReflectors = [],
    ) {
        $this->templateReflectorsByName = array_column($templateReflectors, null, 'name');
    }

    public function class(string $name): mixed
    {
        return types::object($name);
    }

    public function static(string $self): mixed
    {
        return types::static($self);
    }

    public function constant(string $name): mixed
    {
        return types::constant($name);
    }

    public function template(string $name): TemplateType
    {
        if (!isset($this->templateReflectorsByName[$name])) {
            throw new ReflectionException();
        }

        return $this->templateReflectorsByName[$name]->type();
    }

    public function classOrConstants(string $classCandidate, array $constantCandidates): mixed
    {
        if ($this->classExistenceChecker->classExists($classCandidate)) {
            return types::object($classCandidate);
        }

        foreach ($constantCandidates as $constant) {
            if (\defined($constant)) {
                return types::constant($constant);
            }
        }

        throw new ReflectionException(sprintf(
            'Neither class "%s", nor constant%s "%s" exist.',
            $classCandidate,
            \count($constantCandidates) > 1 ? 's' : '',
            implode('", "', $constantCandidates),
        ));
    }

    /**
     * @param array<TemplateReflector> $templateReflectors
     */
    public function withTemplateReflectors(array $templateReflectors): self
    {
        return new self($this->classExistenceChecker, [
            ...$this->templateReflectorsByName,
            ...array_column($templateReflectors, null, 'name'),
        ]);
    }
}
