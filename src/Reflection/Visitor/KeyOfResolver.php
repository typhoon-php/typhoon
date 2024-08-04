<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Visitor;

use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Visitor\DefaultTypeVisitor;
use function Typhoon\Type\stringify;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Visitor
 * @extends DefaultTypeVisitor<Type>
 */
final class KeyOfResolver extends DefaultTypeVisitor
{
    public function list(Type $type, Type $valueType, array $elements): mixed
    {
        if ($valueType->accept(new IsNever())) {
            return types::listShape(array_map(types::int(...), array_keys($elements)));
        }

        return types::nonNegativeInt;
    }

    public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed
    {
        if ($valueType->accept(new IsNever())) {
            return types::listShape(array_map(types::int(...), array_keys($elements)));
        }

        return types::nonNegativeInt;
    }

    protected function default(Type $type): mixed
    {
        throw new \LogicException(\sprintf('Cannot resolve %s type as int range limit', stringify($type)));
    }
}
