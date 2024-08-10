<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\ShapeElement;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class ArrayType implements Type
{
    /**
     * @param array<ShapeElement> $elements
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
