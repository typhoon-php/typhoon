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

    public function arrayShape(Type $self, array $elements, bool $sealed): mixed
    {
        if (!isSubtype($self, $this->type)) {
            return false;
        }

        foreach ($elements as $element) {
            if (!$element->optional) {
                return true;
            }
        }

        return false;
    }

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
