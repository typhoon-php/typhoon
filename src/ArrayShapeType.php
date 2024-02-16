<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TArray of array
 * @implements Type<TArray>
 */
final class ArrayShapeType implements Type
{
    /**
     * @var array<ArrayElement>
     */
    public readonly array $elements;

    public readonly bool $sealed;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param array<ArrayElement> $elements
     */
    public function __construct(
        array $elements = [],
        bool $sealed = true,
    ) {
        $this->elements = $elements;
        $this->sealed = $sealed;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitArrayShape($this);
    }
}
