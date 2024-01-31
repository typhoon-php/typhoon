<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\NameResolution\NameResolver;
use Typhoon\Reflection\ReflectionException;
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
     * @param list<Type> $templateArguments
     */
    public function __construct(
        private ClassExistenceChecker $classExistenceChecker,
        private readonly array $templateArguments = [],
    ) {}

    public function class(string $name): mixed
    {
        return types::object($name, ...$this->templateArguments);
    }

    public function static(string $self): mixed
    {
        return types::static($self, ...$this->templateArguments);
    }

    public function constant(string $name): mixed
    {
        return types::constant($name);
    }

    public function classTemplate(string $class, string $name): mixed
    {
        return types::template($name, types::atClass($class)/** TODO constraint */);
    }

    public function methodTemplate(string $class, string $method, string $name): mixed
    {
        return types::template($name, types::atMethod($class, $method)/** TODO constraint */);
    }

    public function classOrConstants(string $classCandidate, array $constantCandidates): mixed
    {
        if ($this->classExistenceChecker->classExists($classCandidate)) {
            /** @var class-string $classCandidate */
            return types::object($classCandidate, ...$this->templateArguments);
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
