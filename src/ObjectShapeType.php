<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TObject of object
 * @implements Type<TObject>
 */
final class ObjectShapeType implements Type
{
    /**
     * @var array<string, Property>
     */
    public readonly array $properties;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param array<string, Property> $properties
     */
    public function __construct(
        array $properties = [],
    ) {
        $this->properties = $properties;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitObjectShape($this);
    }
}
