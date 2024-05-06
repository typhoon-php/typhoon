<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsObject extends Comparator
{
    public function closure(Type $self, array $parameters, ?Type $return): mixed
    {
        return true;
    }

    public function namedObject(Type $self, ClassId|AnonymousClassId $class, array $arguments): mixed
    {
        return true;
    }

    public function object(Type $self, array $properties): mixed
    {
        return true;
    }
}
