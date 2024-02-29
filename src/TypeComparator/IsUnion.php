<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsUnion extends Comparator
{
    /**
     * @param non-empty-list<Type> $types
     */
    public function __construct(
        private readonly array $types,
    ) {}

    protected function default(Type $self): mixed
    {
        foreach ($this->types as $type) {
            if (isSubtype($self, of: $type)) {
                return true;
            }
        }

        return false;
    }
}
