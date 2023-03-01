<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T of object
 * @implements Type<T>
 */
final class NamedObjectT implements Type
{
    /**
     * @var list<Type>
     */
    public readonly array $templateArguments;

    /**
     * @no-named-arguments
     * @param class-string<T> $class
     */
    public function __construct(
        public readonly string $class,
        Type ...$templateArguments,
    ) {
        $this->templateArguments = $templateArguments;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNamedObject($this);
    }
}
