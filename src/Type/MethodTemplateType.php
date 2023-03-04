<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class MethodTemplateType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param non-empty-string $name
     * @param class-string $class
     * @param non-empty-string $method
     */
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        public readonly string $method,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitMethodTemplate($this);
    }
}
