<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @psalm-immutable
 * @implements Type<mixed>
 */
final class IntersectionType implements Type
{
    /**
     * @param non-empty-list<Type> $types
     */
    public function __construct(
        private readonly array $types,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->intersection($this, $this->types);
    }
}
