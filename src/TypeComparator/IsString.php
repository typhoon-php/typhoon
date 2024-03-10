<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsString extends Comparator
{
    public function classConstant(Type $self, Type $class, string $name): mixed
    {
        // TODO full class constant support.
        return $name === 'class';
    }

    public function classString(Type $self, Type $class): mixed
    {
        return true;
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return \is_string($value);
    }

    public function numericString(Type $self): mixed
    {
        return true;
    }

    public function string(Type $self): mixed
    {
        return true;
    }

    public function truthyString(Type $self): mixed
    {
        return true;
    }
}
