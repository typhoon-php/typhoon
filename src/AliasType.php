<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TType
 * @implements Type<TType>
 */
final class AliasType implements Type
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $class,
        private readonly string $name,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->alias($this, $this->class, $this->name);
    }
}
