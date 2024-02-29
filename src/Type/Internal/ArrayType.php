<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\ArrayElement;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

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
