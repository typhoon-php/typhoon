<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<array>
 */
final class ArrayShapeType implements Type
{
    /**
     * @param array<ArrayElement> $elements
     */
    public function __construct(
        private readonly array $elements,
        private readonly bool $sealed,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->arrayShape($this, $this->elements, $this->sealed);
    }
}
