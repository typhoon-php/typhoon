<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<array<mixed>>
 */
final class ArrayType implements Type
{
    /**
     * @param array<ArrayElement> $elements
     */
    public function __construct(
        private readonly Type $key,
        private readonly Type $value,
        private readonly array $elements,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->array($this, $this->key, $this->value, $this->elements);
    }
}
