<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Visitor;

use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;
use function Typhoon\Type\stringify;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Visitor
 * @extends DefaultTypeVisitor<int>
 */
final class IntLimitResolver extends DefaultTypeVisitor
{
    public function intValue(Type $type, int $value): mixed
    {
        return $value;
    }

    protected function default(Type $type): mixed
    {
        throw new \LogicException(\sprintf('Cannot resolve %s type as int range limit', stringify($type)));
    }
}
