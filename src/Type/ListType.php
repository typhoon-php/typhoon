<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<list<mixed>>
 */
final class ListType implements Type
{
    /**
     * @param array<int, ArrayElement> $elements
     */
    public function __construct(
        private readonly Type $value,
        private readonly array $elements,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->list($this, $this->value, $this->elements);
    }
}
