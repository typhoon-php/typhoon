<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsList extends Comparator
{
    public function __construct(
        private readonly Type $type,
    ) {}

    public function arrayShape(Type $self, array $elements, bool $sealed): mixed
    {
        if (!$sealed) {
            return false;
        }

        if ($elements === []) {
            return true;
        }

        $keys = [];

        foreach ($elements as $key => $element) {
            if (!isSubtype($element->type, $this->type)) {
                return false;
            }

            $keys[] = $key;
        }

        sort($keys);

        return $keys === array_keys($keys);
    }

    public function list(Type $self, Type $value): mixed
    {
        return isSubtype($value, $this->type);
    }
}
