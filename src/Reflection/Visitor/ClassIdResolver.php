<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Visitor;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;
use function Typhoon\Type\stringify;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Visitor
 * @extends DefaultTypeVisitor<NamedClassId|AnonymousClassId>
 */
final class ClassIdResolver extends DefaultTypeVisitor
{
    public function namedObject(Type $type, NamedClassId|AnonymousClassId $classId, array $typeArguments): mixed
    {
        return $classId;
    }

    public function self(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        if ($resolvedClassId === null) {
            return $this->default($type);
        }

        return $resolvedClassId;
    }

    public function parent(Type $type, array $typeArguments, ?NamedClassId $resolvedClassId): mixed
    {
        if ($resolvedClassId === null) {
            return $this->default($type);
        }

        return $resolvedClassId;
    }

    public function static(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        if ($resolvedClassId === null) {
            return $this->default($type);
        }

        return $resolvedClassId;
    }

    protected function default(Type $type): mixed
    {
        throw new \LogicException(\sprintf('Cannot resolve %s type as class', stringify($type)));
    }
}
