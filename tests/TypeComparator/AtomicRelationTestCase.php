<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

abstract class AtomicRelationTestCase extends RelationTestCase
{
    final protected static function xSubtypeOfY(): iterable
    {
        $of = static::type();

        foreach (static::subtypes() as $subtype) {
            yield [$subtype, $of];
        }
    }

    final protected static function xNotSubtypeOfY(): iterable
    {
        $of = static::type();

        foreach (static::nonSubtypes() as $nonSubtype) {
            yield [$nonSubtype, $of];
        }
    }

    abstract protected static function type(): Type;

    /**
     * @return iterable<Type>
     */
    abstract protected static function subtypes(): iterable;

    /**
     * @return iterable<Type>
     */
    abstract protected static function nonSubtypes(): iterable;
}
