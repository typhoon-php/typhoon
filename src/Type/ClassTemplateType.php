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
final class ClassTemplateType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param non-empty-string $name
     * @param class-string $class
     */
    public function __construct(
        public readonly string $name,
        public readonly string $class,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitClassTemplate($this);
    }
}
