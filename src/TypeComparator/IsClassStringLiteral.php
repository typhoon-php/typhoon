<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsClassStringLiteral extends Comparator
{
    /**
     * @param non-empty-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {}

    public function classStringLiteral(Type $self, string $class): mixed
    {
        return $class === $this->class;
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return $value === $this->class;
    }
}
