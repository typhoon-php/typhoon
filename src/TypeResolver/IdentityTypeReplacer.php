<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class IdentityTypeReplacer extends RecursiveTypeReplacer
{
    public function visitNamedClassString(Type\NamedClassStringType $type): mixed
    {
        return $type;
    }

    public function visitNonEmptyList(Type\NonEmptyListType $type): mixed
    {
        return $type;
    }

    public function visitList(Type\ListType $type): mixed
    {
        return $type;
    }

    public function visitArrayShape(Type\ArrayShapeType $type): mixed
    {
        return $type;
    }

    public function visitNonEmptyArray(Type\NonEmptyArrayType $type): mixed
    {
        return $type;
    }

    public function visitArray(Type\ArrayType $type): mixed
    {
        return $type;
    }

    public function visitIterable(Type\IterableType $type): mixed
    {
        return $type;
    }

    public function visitNamedObject(Type\NamedObjectType $type): mixed
    {
        return $type;
    }

    public function visitStatic(Type\StaticType $type): mixed
    {
        return $type;
    }

    public function visitObjectShape(Type\ObjectShapeType $type): mixed
    {
        return $type;
    }

    public function visitClosure(Type\ClosureType $type): mixed
    {
        return $type;
    }

    public function visitCallable(Type\CallableType $type): mixed
    {
        return $type;
    }

    public function visitKeyOf(Type\KeyOfType $type): mixed
    {
        return $type;
    }

    public function visitValueOf(Type\ValueOfType $type): mixed
    {
        return $type;
    }

    public function visitConditional(Type\ConditionalType $type): mixed
    {
        return $type;
    }

    public function visitIntersection(Type\IntersectionType $type): mixed
    {
        return $type;
    }

    public function visitUnion(Type\UnionType $type): mixed
    {
        return $type;
    }
}
