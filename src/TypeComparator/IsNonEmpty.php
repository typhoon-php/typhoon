<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsNonEmpty extends Comparator
{
    public function __construct(
        private readonly Type $type,
    ) {}

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return isSubtype($self, $this->type)
            && $value !== ''
            && $value !== 0
            && $value !== false;
    }

    public function nonEmpty(Type $self, Type $type): mixed
    {
        return isSubtype($self, $this->type);
    }
}
