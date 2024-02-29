<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Property;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<object>
 */
final class ObjectShapeType implements Type
{
    /**
     * @param non-empty-array<string, Property> $properties
     */
    public function __construct(
        private readonly array $properties,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->objectShape($this, $this->properties);
    }
}
