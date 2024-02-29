<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\DefaultTypeVisitor;
use Typhoon\Type\Type;
use Typhoon\Type\Variance;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 * @extends DefaultTypeVisitor<bool>
 */
abstract class Comparator extends DefaultTypeVisitor
{
    public function intersection(Type $self, array $types): mixed
    {
        foreach ($types as $type) {
            if ($type->accept($this)) {
                return true;
            }
        }

        return false;
    }

    public function literal(Type $self, Type $type): mixed
    {
        return $type->accept($this);
    }

    public function never(Type $self): mixed
    {
        return true;
    }

    public function nonEmpty(Type $self, Type $type): mixed
    {
        return $type->accept($this);
    }

    public function union(Type $self, array $types): mixed
    {
        foreach ($types as $type) {
            if (!$type->accept($this)) {
                return false;
            }
        }

        return true;
    }

    public function varianceAware(Type $self, Type $type, Variance $variance): mixed
    {
        return $type->accept($this);
    }

    protected function default(Type $self): mixed
    {
        return false;
    }
}
