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
final class ObjectType implements Type
{
    /**
     * @param non-empty-array<string, ShapeElement> $properties
     */
    public function __construct(
        private readonly array $properties,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->object($this, $this->properties);
    }
}
