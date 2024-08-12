<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\PhpDoc
 * @extends DefaultTypeVisitor<?array{NamedClassId|AnonymousClassId, list<Type>}>
 */
final class NamedObjectTypeDestructurizer extends DefaultTypeVisitor
{
    public function namedObject(Type $type, NamedClassId|AnonymousClassId $classId, array $typeArguments): mixed
    {
        return [$classId, $typeArguments];
    }

    protected function default(Type $type): mixed
    {
        return null;
    }
}
