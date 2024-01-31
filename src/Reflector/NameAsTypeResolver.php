<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\NameResolution\NameResolver;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\TemplateReflection;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-type TemplateReflector = \Closure(): TemplateReflection
 * @implements NameResolver<Type, TemplateReflector>
 */
final class NameAsTypeResolver implements NameResolver
{
    public function __construct(
        private readonly ClassExistenceChecker $classExistenceChecker,
    ) {}

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

    public function classTemplate(string $class, string $name, mixed $metadata): mixed
    {
        return types::template(
            name: $name,
            declaredAt: types::atClass($class),
            constraint: $metadata()->getConstraint(),
        );
    }

    public function methodTemplate(string $class, string $method, string $name, mixed $metadata): mixed
    {
        return types::template(
            name: $name,
            declaredAt: types::atMethod($class, $method),
            constraint: $metadata()->getConstraint(),
        );
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
}
