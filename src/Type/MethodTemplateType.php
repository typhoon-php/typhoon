<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class MethodTemplateType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param class-string $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $class,
        public readonly string $method,
        public readonly string $name,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitMethodTemplate($this);
    }
}
