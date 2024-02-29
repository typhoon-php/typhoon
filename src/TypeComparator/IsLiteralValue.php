<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsLiteralValue extends Comparator
{
    public function __construct(
        private readonly float|bool|int|string $value,
    ) {}

    public function classStringLiteral(Type $self, string $class): mixed
    {
        return $class === $this->value;
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return $value === $this->value;
    }
}
